<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
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
        $this->assertSame('Hello World', (string)$response->getBody());
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
        $this->assertSame('+middlewarehandler', (string)$response->getBody());
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

        $router->group('/api', function (Wrouter $r) {
            $r->get('/ping', function (ServerRequestInterface $request, ResponseInterface $response) {
                $response->getBody()->write('pong');
                return $response->withStatus(200);
            });
        }, [$middleware]);

        $response = $router->dispatcher('/api/ping');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(' [grouped]pong', (string)$response->getBody());
    }

    public function testGetRouteWithMiddleware(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/hello';

        $log = [];

        $middleware = new class($log) implements MiddlewareInterface {
            public function __construct(public array &$log)
            {
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->log[] = 'middleware';
                return $handler->handle($request);
            }
        };

        $router = new Wrouter(new Response(), ServerRequestFactory::fromGlobals());

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

    public function testLowercaseHttpMethodIsNormalized(): void
    {
        $request = new ServerRequest([], [], new Uri('/ping'), 'get'); // minúsculo
        $router = new Wrouter();
        $router->setRequest($request);

        $router->get('/ping', function ($req, $res) {
            $res->getBody()->write('pong');
            return $res;
        });

        $response = $router->dispatcher('/ping');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('pong', (string)$response->getBody());
    }

    public function testGroupPrefixNormalization(): void
    {
        $request = new ServerRequest([], [], new Uri('/api/test'), 'GET');
        $router = new Wrouter();
        $router->setRequest($request);

        $router->group('/api/', function ($r) {
            $r->get('/test', function ($req, $res) {
                $res->getBody()->write('ok');
                return $res;
            });
        });

        $response = $router->dispatcher('/api/test');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', (string)$response->getBody());
    }

    public function testDynamicRouteMatchesProperly(): void
    {
        $request = new ServerRequest([], [], new Uri('/user/10'), 'GET');
        $router = new Wrouter();
        $router->setRequest($request);

        $router->get('/user/:id', function ($req, $res) {
            $id = $req->getAttribute('id');
            $res->getBody()->write('id=' . $id);
            return $res;
        });

        $response = $router->dispatcher('/user/10');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('id=10', (string)$response->getBody());
    }


    public function testDispatcherReturns404WhenOnlyHandlerIsMissing(): void
    {
        $router = new Wrouter();
        $request = new ServerRequest([], [], new Uri('/missing'), 'GET');
        $router->setRequest($request);

        // Não registramos rota nenhuma
        $response = $router->dispatcher('/missing');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDispatcherHandlesNullResponseSafely(): void
    {
        $router = new Wrouter(null, new ServerRequest([], [], new Uri('/x'), 'GET'));

        $response = $router->dispatcher('/x');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testMiddlewareChainStopsOnNon200AndNon302(): void
    {
        $router = new Wrouter();
        $router->setRequest(new ServerRequest([], [], new Uri('/stop'), 'GET'));

        $router->get('/stop', function ($req, $res) {
            return $res->withStatus(500);
        }, [
            new class implements MiddlewareInterface {
                public function process(ServerRequestInterface $req, RequestHandlerInterface $handler): ResponseInterface
                {
                    $resp = $handler->handle($req);
                    $resp->getBody()->write('FAILED');
                    return $resp;
                }
            }
        ]);

        $response = $router->dispatcher('/stop');

        $this->assertSame(500, $response->getStatusCode());
        $body = (string)$response->getBody();
        $this->assertStringContainsString('FAILED', $body);
        $this->assertStringContainsString('{"status":500,"message":"Internal Server Error"}', $body);
    }

    public function testGroupTrimsSlashes()
    {
        $router = new \Omegaalfa\Wrouter\Router\Router();
        $router->group('/api/', function($r) {
            $this->assertEquals('api', $r->getCurrentGroup());
        });
    }

    public function testMultipleMiddlewaresAreExecutedInOrder(): void
    {
        $log = [];

        $middlewareA = new class($log) implements MiddlewareInterface {
            public function __construct(public array &$log) {}
            public function process(ServerRequestInterface $r, RequestHandlerInterface $h): ResponseInterface {
                $this->log[] = 'A';
                return $h->handle($r);
            }
        };

        $middlewareB = new class($log) implements MiddlewareInterface {
            public function __construct(public array &$log) {}
            public function process(ServerRequestInterface $r, RequestHandlerInterface $h): ResponseInterface {
                $this->log[] = 'B';
                return $h->handle($r);
            }
        };

        $router = new Wrouter();
        $router->setRequest(new ServerRequest([], [], new Uri('/x'), 'GET'));

        $router->get('/x', fn($req, $res) => $res->withStatus(200), [$middlewareA, $middlewareB]);

        $router->dispatcher('/x');

        $this->assertSame(['A', 'B'], $log);
    }
    public function testTreeRouterReturnsNullForUnknownRoute(): void
    {
        $tree = new \Omegaalfa\Wrouter\Router\TreeRouter();
        $result = $tree->findRoute('/xyz/123');

        $this->assertNull($result);
    }

    public function testRouterStopsOnMiddlewareError(): void
    {
        $router = new Wrouter();

        $router->get('/test/', function (){

        }, [
            new class implements MiddlewareInterface {

                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    return new Response()->withStatus(401);
                }
            }
        ]);
        $resp = $router->dispatcher('/test');

        $this->assertEquals(401, $resp->getStatusCode());
    }

}
