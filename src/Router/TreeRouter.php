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

    /** @var array<string, array{handler:callable,middlewares:array<int,MiddlewareInterface>,params:array<string,string>}> */
    private array $staticMap = [];

    /** @var array<string, array{handler:callable,middlewares:array<int,MiddlewareInterface>,params:array<string,string>}> */
    private array $routeCache = [];

    /**
     * @var int
     */
    private int $cacheLimit = 2048; // adjust as needed

    public function __construct()
    {
        $this->root = new TreeNode();
    }

    /**
     * @return array{handler: callable, middlewares: array<int, MiddlewareInterface>, params: array<string,string>}|null
     */
    public function findRoute(string $path): ?array
    {
        // normalize incoming path: ensure leading slash and no trailing multiple slashes
        $normalized = '/' . preg_replace('#/+#', '/', ltrim($path, '/'));

        // static fast map
        if (isset($this->staticMap[$normalized])) {
            return $this->staticMap[$normalized];
        }

        // cache
        if (isset($this->routeCache[$normalized])) {
            // move to end (simple LRU emulation)
            $entry = $this->routeCache[$normalized];
            unset($this->routeCache[$normalized]);
            $this->routeCache[$normalized] = $entry;
            return $entry;
        }

        $currentNode = $this->root;
        $p = ltrim($normalized, '/');
        $parts = $p === '' ? [] : explode('/', $p);
        $params = [];

        foreach ($parts as $segment) {
            if ($segment === '') continue;

            // 1 — exact child
            if (isset($currentNode->children[$segment])) {
                $currentNode = $currentNode->children[$segment];
                continue;
            }

            // 2 — param child
            if ($currentNode->paramChild !== null) {
                $params[$currentNode->paramName ?? 'param'] = $segment;
                $currentNode = $currentNode->paramChild;
                continue;
            }

            // 3 — no match
            return null;
        }

        if ($currentNode->isEndOfRoute && is_callable($currentNode->handler)) {
            $result = [
                'handler' => $currentNode->handler,
                'middlewares' => $currentNode->middlewares,
                'params' => $params,
            ];

            // store in cache
            $this->routeCache[$normalized] = $result;
            if (count($this->routeCache) > $this->cacheLimit) {
                // drop oldest
                reset($this->routeCache);
                $k = key($this->routeCache);
                unset($this->routeCache[$k]);
            }

            return $result;
        }

        return null;
    }

    /**
     * @param string $path
     * @param callable $handler
     * @param array<int, MiddlewareInterface> $middlewares
     */
    protected function addRoute(string $path, callable $handler, array $middlewares = []): void
    {
        // normalize without excessive trimming
        $p = ltrim($path, '/');

        // register static fast-path
        if (!str_contains($p, ':')) {
            $this->staticMap['/' . ($p)] = [
                'handler' => $handler,
                'middlewares' => $middlewares,
                'params' => [],
            ];
            // still store in trie so hierarchical logic remains
        }

        $currentNode = $this->root;
        $parts = $p === '' ? [] : explode('/', $p);

        foreach ($parts as $segment) {
            if ($segment === '') {
                continue;
            }

            if ($segment[0] === ':') {
                $name = substr($segment, 1);
                if ($currentNode->paramChild === null) {
                    $currentNode->paramChild = new TreeNode();
                    $currentNode->paramName = $name;
                }
                // if param name differs, we keep first registered param name (common behavior)
                $currentNode = $currentNode->paramChild;
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
}
