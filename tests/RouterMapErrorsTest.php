<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Router;
use Omegaalfa\Wrouter\Router\Wrouter;
use PHPUnit\Framework\TestCase;

final class RouterMapErrorsTest extends TestCase
{
    private function createRouterWithRequest(string $uri, string $method): Router
    {
        $request = new ServerRequest([], [], new Uri($uri), $method);

        return new class(new Response(), $request) extends Router {
            public function callMap(string $method, string $path, callable $handler): void
            {
                $this->map($method, $path, $handler);
            }
        };
    }

    public function testMapThrowsForUnsupportedHttpMethod(): void
    {
        $router = $this->createRouterWithRequest('/foo', 'GET');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP method not supported');

        $router->callMap('FOO', '/foo', static function () {
        });
    }

    public function testMapRegistersRouteEvenIfRequestMethodDiffers(): void
    {
        $router = new class(new Response(), new ServerRequest([], [], new Uri('/foo'), 'POST')) extends Wrouter {
            public function register(string $path, callable $handler): void
            {
                $this->map('GET', $path, $handler);
            }
        };

        $router->register('/foo', function () {
            return new Response();
        });

        $router->setRequest(new ServerRequest([], [], new Uri('/foo'), 'GET'));
        $response = $router->dispatcher('/foo');

        $this->assertSame(200, $response->getStatusCode());
    }
}
