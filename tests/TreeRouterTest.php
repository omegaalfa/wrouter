<?php

declare(strict_types=1);

use Omegaalfa\Wrouter\Router\TreeRouter;
use PHPUnit\Framework\TestCase;

final class TreeRouterTest extends TestCase
{
    private function createRouter(): TreeRouter
    {
        return new class extends TreeRouter {
            public function exposeAddRoute(string $path, callable $handler): void
            {
                $this->addRoute($path, $handler, []);
            }

            public function exposeFindRoute(string $path): ?array
            {
                return $this->findRoute($path);
            }
        };
    }

    public function testAddAndFindSimpleRoute(): void
    {
        $router = $this->createRouter();

        $handler = static function () {
            return 'ok';
        };

        $router->exposeAddRoute('/simple/path', $handler);

        $result = $router->exposeFindRoute('/simple/path');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('handler', $result);
        $this->assertSame($handler, $result['handler']);
        $this->assertSame([], $result['middlewares']);
    }

    public function testFindRouteReturnsNullForUnknownPath(): void
    {
        $router = $this->createRouter();

        $router->exposeAddRoute('/known', static function () {
        });

        $this->assertNull($router->exposeFindRoute('/other'));
    }

    public function testAddRouteIgnoresEmptySegments(): void
    {
        $router = $this->createRouter();

        $handler = static function () {
        };

        // múltiplas barras e barra final devem ser normalizadas
        $router->exposeAddRoute('///foo//bar/', $handler);

        $this->assertNotNull($router->exposeFindRoute('/foo/bar'));
    }
}

