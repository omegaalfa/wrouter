<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Router;
use Omegaalfa\Wrouter\Router\TreeNode;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class RouterErrorHandlerTest extends TestCase
{
    private function createRouter(): Router
    {
        $request = new ServerRequest([], [], new Uri('/error'), 'GET');

        return new class(new Response(), $request) extends Router {
            public function probeFind(string $path): ?ResponseInterface
            {
                return $this->findRouteNoCached($path);
            }

            public function forceNonClosureHandler(string $path): void
            {
                $parts = explode('/', trim($path, '/'));

                $current = $this->root;
                foreach ($parts as $segment) {
                    if ($segment === '') {
                        continue;
                    }
                    if (!isset($current->children[$segment])) {
                        $current->children[$segment] = new TreeNode();
                    }
                    $current = $current->children[$segment];
                }

                $current->isEndOfRoute = true;
                $current->handler = new \stdClass(); // não é Closure
            }
        };
    }

    public function testFindRouteNoCachedReturns404WhenHandlerIsMissing(): void
    {
        $router = $this->createRouter();

        $response = $router->probeFind('/error');

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }
}
