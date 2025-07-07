<?php

declare(strict_types=1);

namespace Omegaalfa\Wrouter\Router;


use Psr\Http\Server\MiddlewareInterface;

class TreeRouter
{
    /**
     * @var TreeNode
     */
    protected TreeNode $root;

    public function __construct()
    {
        $this->root = new TreeNode();
    }

    /**
     * @param string $path
     * @param callable $handler
     * @param array<int, MiddlewareInterface> $middlewares
     *
     * @return void
     */
    protected function addRoute(string $path, callable $handler, array $middlewares = []): void
    {
        $currentNode = $this->root;
        $parts = explode('/', trim($path, '/'));

        foreach ($parts as $key => $segment) {
            if (empty($segment)) {
                continue;
            }

            if (!isset($currentNode->children[$segment])) {
                $currentNode->children[$segment] = new TreeNode();
            }

            $currentNode = $currentNode->children[$segment];
        }

        $currentNode->isEndOfRoute = true;
        $currentNode->handler = $handler;
        $currentNode->middlewares = $middlewares;
    }


    /**
     * @param string $path
     *
     * @return array{handler: callable, middlewares: array<int, MiddlewareInterface>}|null
     */
    protected function findRoute(string $path): ?array
    {
        $currentNode = $this->root;
        $parts = explode('/', trim($path, '/'));

        foreach ($parts as $segment) {
            if (empty($segment)) {
                continue;
            }
            if (!isset($currentNode->children[$segment])) {
                return null;
            }

            $currentNode = $currentNode->children[$segment];
        }
        if ($currentNode->isEndOfRoute && is_callable($currentNode->handler)) {
            return [
                'handler' => $currentNode->handler,
                'middlewares' => $currentNode->middlewares,
            ];
        }

        return null;
    }
}