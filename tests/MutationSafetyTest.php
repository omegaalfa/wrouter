<?php


declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Dispatcher;
use Omegaalfa\Wrouter\Router\Wrouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class MutationSafetyTest extends TestCase
{
    public function testDispatcherAddsAttributesWhenParamsPresent(): void
    {
        $params = ['id' => '123', 'name' => 'wesley'];

        $handler = function ($request, ResponseInterface $response) {
            $id = $request->getAttribute('id');
            $name = $request->getAttribute('name');
            $response->getBody()->write(sprintf('%s|%s', $id, $name));
            return $response->withStatus(200);
        };

        $response = new Response();
        $dispatcher = new Dispatcher($handler, $response, $params);

        $request = (new ServerRequest([], [], new Uri('/hello'), 'GET'));
        $result = $dispatcher->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('123|wesley', (string)$result->getBody());
    }

    public function testDispatcherHandlesNullRequestWhenNoParams(): void
    {
        $handler = function ($request, ResponseInterface $response) {
            // When no request is provided, handler should gracefully accept null
            $body = $request === null ? 'no-request' : 'has-request';
            $response->getBody()->write($body);
            return $response->withStatus(200);
        };

        $response = new Response();
        $dispatcher = new Dispatcher($handler, $response, []);

        $result = $dispatcher->handle(null);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('no-request', (string)$result->getBody());
    }

    public function testMapAcceptsLowercaseMethod(): void
    {
        // map is protected on Router; use Reflection to call it with lowercase method
        $router = new Wrouter();

        $ref = new ReflectionObject($router);
        $map = $ref->getParentClass()->getMethod('map');
        $map->setAccessible(true);

        // register route using lowercase 'get' intentionally to detect mutation removing strtoupper
        $map->invoke($router, 'get', '/lowercase-test', function ($req, $res) {
            $res->getBody()->write('ok-lower');
            return $res->withStatus(200);
        });

        // dispatch should find this route
        $response = $router->dispatcher('/lowercase-test');

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('ok-lower', (string)$response->getBody());
    }

    public function testConstructorUsesProvidedResponseInstance(): void
    {
        // Create a Response with distinctive header so we can detect it was used
        $custom = new Response();
        $custom = $custom->withHeader('X-CUSTOM-INIT', '1');

        $router = new Wrouter($custom, ServerRequestFactory::fromGlobals());

        // register a route that returns the incoming $response unchanged
        $router->get('/ctor-response', function ($req, $res) {
            // do nothing to response
            return $res->withStatus(200);
        });

        $resp = $router->dispatcher('/ctor-response');

        $this->assertInstanceOf(ResponseInterface::class, $resp);
        $this->assertSame(200, $resp->getStatusCode());
        // If constructor respected the provided Response, header must be present
        $this->assertTrue($resp->hasHeader('X-CUSTOM-INIT'));
        $this->assertSame(['1'], $resp->getHeader('X-CUSTOM-INIT'));
    }

    public function testGroupPrefixTrimAndBuildFullPath(): void
    {
        $router = new Wrouter();

        $router->group('/api/', function (Wrouter $r) {
            $r->get('/ping', function ($req, $res) {
                $res->getBody()->write('pong');
                return $res->withStatus(200);
            });
        });

        $resp = $router->dispatcher('/api/ping');
        $this->assertInstanceOf(ResponseInterface::class, $resp);
        $this->assertSame(200, $resp->getStatusCode());
        $this->assertSame('pong', (string)$resp->getBody());
    }

    public function testFindRouteRespectsTrimmedPaths(): void
    {
        $router = new Wrouter();

        // register route with leading/trailing slashes
        $router->get('/hello/', function ($req, $res) {
            $res->getBody()->write('hello');
            return $res->withStatus(200);
        });

        // dispatch with and without extra slashes
        $r1 = $router->dispatcher('/hello');


        $this->assertSame(200, $r1->getStatusCode());

        $this->assertSame('hello', (string)$r1->getBody());

    }

    public function testAddRouteAndFindRouteWithParameters(): void
    {
        $router = new Wrouter();

        $router->get('/user/:id/:section', function ($req, $res) {
            $id = $req->getAttribute('id');
            $section = $req->getAttribute('section');
            $res->getBody()->write(sprintf('%s#%s', $id, $section));
            return $res->withStatus(200);
        });

        $resp = $router->dispatcher('/user/42/profile');

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertSame('42#profile', (string)$resp->getBody());
    }
}
