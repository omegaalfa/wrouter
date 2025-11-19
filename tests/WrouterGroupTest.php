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

final class WrouterGroupTest extends TestCase
{
    public function testGroupPrefixAndMiddlewaresAreApplied(): void
    {
        $request = new ServerRequest([], [], new Uri('/api/admin/ping'), 'GET');
        $router = new Wrouter(new Response(), $request);

        $router->group('/api', function (Wrouter $r) {
            $r->get('/admin/ping', function (ServerRequestInterface $request, ResponseInterface $response) {
                $response->getBody()->write('pong');
                return $response->withStatus(200);
            });
        }, [
            new class implements MiddlewareInterface {
                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    $response = $handler->handle($request);
                    $response->getBody()->write(' [group]');
                    return $response->withStatus(200);
                }
            }
        ]);

        $response = $router->dispatcher('/api/admin/ping');

        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('pong', $body);
        $this->assertStringContainsString('[group]', $body);
    }

    public function testStaticAndDynamicRoutesDoNotConflict(): void
    {
        $request = new ServerRequest([], [], new Uri('/users'), 'GET');
        $router = new Wrouter(new Response(), $request);

        $router->get('/users', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response->withStatus(200)->withHeader('X-Type', 'list');
        });

        $router->get('/users/:id', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response->withStatus(200)->withHeader('X-Type', 'detail');
        });

        $listResponse = $router->dispatcher('/users');
        $this->assertSame('list', $listResponse->getHeaderLine('X-Type'));

        $requestId = new ServerRequest([], [], new Uri('/users/5'), 'GET');
        $routerId = new Wrouter(new Response(), $requestId);

        $routerId->get('/users', function () { return new Response(); }); // register same set on new router instance
        $routerId->get('/users/:id', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response->withStatus(200)->withHeader('X-Type', 'detail');
        });

        $detailResponse = $routerId->dispatcher('/users/5');
        $this->assertSame('detail', $detailResponse->getHeaderLine('X-Type'));
    }
}
