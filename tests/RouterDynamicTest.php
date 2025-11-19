<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Wrouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RouterDynamicTest extends TestCase
{
    public function testDynamicRouteWithParamIsDispatched(): void
    {
        $request = new ServerRequest([], [], new Uri('/users/123'), 'GET');
        $router = new Wrouter(new Response(), $request);

        $router->get('/users/:id', function (ServerRequestInterface $req, ResponseInterface $res) {
            $res->getBody()->write('user');
            return $res->withStatus(200);
        });

        $response = $router->dispatcher('/users/123');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('user', (string) $response->getBody());
    }
}

