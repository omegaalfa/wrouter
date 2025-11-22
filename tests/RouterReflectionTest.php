<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RouterReflectionTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router(new Response(), new ServerRequest([], [], new Uri('/'), 'GET'));
    }

    public function testBuildFullPathAddsGroupPrefix(): void
    {
        $reflection = new \ReflectionClass(Router::class);
        $groupProp = $reflection->getProperty('group');
        $groupProp->setAccessible(true);
        $groupProp->setValue($this->router, 'admin');

        $method = $reflection->getMethod('buildFullPath');
        $method->setAccessible(true);

        $this->assertSame('/admin/dashboard', $method->invoke($this->router, '/dashboard'));

        $groupProp->setValue($this->router, null);
        $this->assertSame('/dashboard', $method->invoke($this->router, '/dashboard'));
    }

    public function testHandlerMiddlewareWrappingOrder(): void
    {
        $reflection = new \ReflectionClass(Router::class);
        $method = $reflection->getMethod('handlerMiddleware');
        $method->setAccessible(true);

        $log = [];

        $middlewares = [
            new class($log) implements Psr\Http\Server\MiddlewareInterface {
                public function __construct(private array &$log)
                {
                }

                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    $this->log[] = 'first';
                    $response = $handler->handle($request);
                    $this->log[] = 'first-after';
                    return $response;
                }
            },
            new class($log) implements Psr\Http\Server\MiddlewareInterface {
                public function __construct(private array &$log)
                {
                }

                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    $this->log[] = 'second';
                    return $handler->handle($request);
                }
            },
        ];

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = new Response();
                $response->getBody()->write('handler');
                return $response;
            }
        };

        $response = $method->invoke($this->router, $middlewares, $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['first', 'second', 'first-after'], $log);
    }
}
