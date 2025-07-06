<?php

declare(strict_types=1);

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Router;
use Omegaalfa\Wrouter\Router\Wrouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouterTest extends TestCase
{
    public function testSimpleGetRouteIsDispatched(): void
    {
        $request = new ServerRequest([], [], new Uri('/hello'), 'GET');
        $router = new Wrouter();
        $router->setRequest($request);

        $router->get('/hello', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');
            return $response->withStatus(200);
        });

        $response = $router->dispatcher('/hello');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testRouteNotFoundReturns404(): void
    {
        $router = new Wrouter();

        $request = new ServerRequest([], [], new Uri('/notfound'), 'GET');
        $router->setRequest($request);

        $response = $router->dispatcher('/notfound');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testMiddlewareIsApplied(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);
                $response->getBody()->write('+middleware');
                return $response;
            }
        };

        $request = new ServerRequest([], [], new Uri('/test'), 'GET');
        $router = new Wrouter();
        $router->setRequest($request);

        $router->get('/test', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('handler');
            return $response->withStatus(200);
        }, [$middleware]);

        $response = $router->dispatcher('/test');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('+middlewarehandler', (string) $response->getBody());
    }

    public function testGroupWithPrefixAndMiddleware(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);
                $response->getBody()->write(' [grouped]');
                return $response;
            }
        };

        $request = new ServerRequest([], [], new Uri('/api/ping'), 'GET');
        $router = new Wrouter();
        $router->setRequest($request);

        $router->group('/api', function (Router $r) {
            $r->get('/ping', function (ServerRequestInterface $request, ResponseInterface $response) {
                $response->getBody()->write('pong');
                return $response->withStatus(200);
            });
        }, [$middleware]);

        $response = $router->dispatcher('/api/ping');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(' [grouped]pong', (string) $response->getBody());
    }

    public function testGetRouteWithMiddleware(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/hello';

        $log = [];

        $middleware = new class($log) implements MiddlewareInterface {
            public function __construct(public array &$log) {}

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->log[] = 'middleware';
                return $handler->handle($request);
            }
        };

        $router = new Wrouter(new \Laminas\Diactoros\Response(), \Laminas\Diactoros\ServerRequestFactory::fromGlobals());

        $router->get('/hello', function ($request, $response) use (&$log) {
            $log[] = 'handler';
            $response->getBody()->write('Hello World');
            return $response->withStatus(200);
        }, [$middleware]);

        $response = $router->dispatcher('/hello');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Hello World', (string)$response->getBody());

        $this->assertEquals(['middleware', 'handler'], $log);
    }
}
