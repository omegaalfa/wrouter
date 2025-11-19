<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Wrouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class WrouterMiddlewareOrderTest extends TestCase
{
    public function testMiddlewaresRunInReverseOrder(): void
    {
        $request = new ServerRequest([], [], new Uri('/order'), 'GET');
        $router = new Wrouter(new Response(), $request);

        $log = [];

        $first = new class($log) implements MiddlewareInterface {
            public function __construct(private array &$log)
            {
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->log[] = 'first';
                return $handler->handle($request);
            }
        };

        $second = new class($log) implements MiddlewareInterface {
            public function __construct(private array &$log)
            {
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->log[] = 'second';
                $response = $handler->handle($request);
                $this->log[] = 'second-after';
                return $response;
            }
        };

        $router->get('/order', function (ServerRequestInterface $request, ResponseInterface $response) use (&$log) {
            $log[] = 'handler';
            $response->getBody()->write('ok');
            return $response->withStatus(200);
        }, [$first, $second]);

        $response = $router->dispatcher('/order');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['first', 'second', 'second-after', 'handler'], $log);
    }

}
