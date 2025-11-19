<?php

declare(strict_types=1);

namespace Omegaalfa\Wrouter\Router;

use Psr\Http\Server\MiddlewareInterface;

class TreeRouter
{
    protected TreeNode $root;

    public function __construct()
    {
        $this->root = new TreeNode();
    }

    /**
     * @param string $path
     * @param callable $handler
     * @param array<int, MiddlewareInterface> $middlewares
     */
    protected function addRoute(string $path, callable $handler, array $middlewares = []): void
    {
        $currentNode = $this->root;
        $parts = explode('/', trim($path, '/'));

        foreach ($parts as $segment) {
            if ($segment === '') {
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
     * @return array{handler: callable, middlewares: array<int, MiddlewareInterface>, params: array<string,string>}|null
     */
    public function findRoute(string $path): ?array
    {
        $currentNode = $this->root;
        $parts = explode('/', trim($path, '/'));
        $params = [];

        foreach ($parts as $segment) {
            if ($segment === '' || $segment === null) {
                continue;
            }

            // 1 — match exato
            if (isset($currentNode->children[$segment])) {
                $currentNode = $currentNode->children[$segment];
                continue;
            }

            // 2 — match paramétrico somente se EXISTIR filho paramétrico neste nível
            $paramChildKey = null;
            foreach ($currentNode->children as $childKey => $childNode) {
                if ($childKey[0] === ':') {
                    $paramChildKey = $childKey;
                    break;
                }
            }

            if ($paramChildKey !== null) {
                $paramName = substr($paramChildKey, 1);
                $params[$paramName] = $segment;
                $currentNode = $currentNode->children[$paramChildKey];
                continue;
            }

            // 3 — nenhum match → rota inválida
            return null;
        }

        // fim do caminho — só aceita se for final de rota
        if ($currentNode->isEndOfRoute && is_callable($currentNode->handler)) {
            return [
                'handler' => $currentNode->handler,
                'middlewares' => $currentNode->middlewares,
                'params' => $params,
            ];
        }

        return null;
    }


    /**
     * Valida a rota completa usando regex
     */
    private function matchRoute(string $route, string $path): bool
    {
        $pattern = '#^' . preg_replace('/:([a-zA-Z0-9_]+)/', '([a-zA-Z0-9_-]+)', $route) . '$#';
        return preg_match($pattern, $path) === 1;
    }
}
