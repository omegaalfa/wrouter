<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Wrouter;
use PHPUnit\Framework\TestCase;

final class WrouterPutDeleteTest extends TestCase
{
    public function testPutRouteIsDispatched(): void
    {
        $request = new ServerRequest([], [], new Uri('/upsert'), 'PUT');
        $router = new Wrouter(new Response(), $request);

        $router->put('/upsert', function () {
            return new Response();
        });

        $response = $router->dispatcher('/upsert');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDeleteRouteIsDispatched(): void
    {
        $request = new ServerRequest([], [], new Uri('/resource'), 'DELETE');
        $router = new Wrouter(new Response(), $request);

        $router->delete('/resource', function () {
            return new Response();
        });

        $response = $router->dispatcher('/resource');

        $this->assertSame(200, $response->getStatusCode());
    }
}
