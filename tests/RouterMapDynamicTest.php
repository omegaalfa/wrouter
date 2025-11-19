<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class RouterMapDynamicTest extends TestCase
{
    private function createRouter(string $uri, string $method = 'GET'): Router
    {
        return new class(new Response(), new ServerRequest([], [], new Uri($uri), $method)) extends Router {
            public function callMap(string $method, string $path, callable $handler): void
            {
                $this->map($method, $path, $handler);
            }
        };
    }

    public function testMapRegistersDynamicPathForCurrentRequest(): void
    {
        $router = $this->createRouter('/users/42', 'GET');

        $handler = static function (): ResponseInterface {
            return new Response();
        };

        $router->callMap('GET', '/users/:id', $handler);

        $result = $router->findRoute('/users/42');

        $this->assertNotNull($result);
        $this->assertSame($handler, $result['handler']);
    }

    public function testMapDoesNotRegisterWhenPathDoesNotMatch(): void
    {
        $router = $this->createRouter('/orders/1', 'GET');

        $router->callMap('GET', '/users/:id', static function () {
            return new Response();
        });

        $this->assertNull($router->findRoute('/orders/1'));
    }
}
