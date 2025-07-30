<?php

declare(strict_types=1);

namespace Omegaalfa\Wrouter\Router;

use Generator;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Omegaalfa\Wrouter\Http\HttpMethod;
use Omegaalfa\Wrouter\Http\RequestHandler;
use Omegaalfa\Wrouter\Middleware\MiddlewareDispatcher;
use Omegaalfa\Wrouter\Support\ParsedBody;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class Router extends TreeRouter
{

    /**
     * @var ResponseInterface|null
     */
    protected ?ResponseInterface $response;

    /**
     * @var ServerRequestInterface|null
     */
    protected ?ServerRequestInterface $request;

    /**
     * @var array<int, MiddlewareInterface>
     */
    protected array $middlewares;

    /**
     * @var string|null
     */
    protected ?string $group = null;

    /**
     * @var array<int, MiddlewareInterface>
     */
    protected array $groupMiddlewares = [];


    /**
     * @param ResponseInterface|null $response
     * @param ServerRequestInterface|null $request
     * @param array<int, MiddlewareInterface> $middlewares
     */
    public function __construct(?ResponseInterface $response = null, ?ServerRequestInterface $request = null, array $middlewares = [])
    {
        $this->response = $response ?? new Response();
        $this->request = (new ParsedBody())->process($request ?? ServerRequestFactory::fromGlobals());
        $this->middlewares = $middlewares;
        parent::__construct();
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return void
     */
    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * @param string $prefix
     * @param callable $callback
     * @param array<int, MiddlewareInterface> $middlewares
     *
     * @return void
     */
    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $this->groupMiddlewares = $middlewares;
        $previousGroup = $this->group;
        $this->group = trim($prefix, '/');
        $callback($this);
        $this->group = $previousGroup;
        $this->groupMiddlewares = [];
    }

    /**
     * Builds the full path for a route considering the current group prefix.
     *
     * @param string $path The path for the route.
     *
     * @return string The full path including the group prefix (if any).
     */
    private function buildFullPath(string $path): string
    {
        $prefix = $this->group ? '/' . $this->group . '/' : '';
        if (empty($prefix)) {
            return $path;
        }

        return trim($prefix . trim($path, '/'));
    }

    /**
     * @param string $method
     * @param string $path
     * @param callable $handler
     *
     * @return void
     */
    protected function map(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        if (!$this->request) {
            throw new \RuntimeException("Request is null");
        }

        $uriPath = $this->request->getUri()->getPath();
        if (!HttpMethod::isValid($method)) {
            throw new \RuntimeException('HTTP method not supported');
        }

        if ($this->group) {
            $path = $this->buildFullPath($path);
        }

        if ($method !== $this->request->getMethod()) {
            throw new \RuntimeException('Method not valid');
        }

        if ($uriPath === $path) {
            $this->addRoute($path, $handler, array_merge($this->groupMiddlewares, $this->middlewares));
        }

        if ($uriPath !== $path && str_contains($path, ":") && $this->matchRoute($path, $uriPath)) {
            $this->dynamicParams = $path;
            $this->addRoute($uriPath, $handler, array_merge($this->groupMiddlewares, $this->middlewares));
        }
    }

    /**
     * @param string $route
     * @param string $path
     *
     * @return bool
     */
    private function matchRoute(string $route, string $path): bool
    {
        $pattern = '#^' . preg_replace('/:([a-zA-Z0-9_]+)/', '([a-zA-Z0-9_-]+)', $route) . '$#';

        return preg_match($pattern, $path) === 1;
    }

    /**
     * @param string $path
     *
     * @return ?ResponseInterface
     */
    protected function findRouteNoCached(string $path): ?ResponseInterface
    {
        $currentNode = $this->findRoute($path);
        $response = $this->response;
        $handler = $currentNode['handler'] ?? null;
        $middlewares = $currentNode['middlewares'] ?? [];
        $params = [];

        if (is_null($currentNode) || is_null($handler)) {
            return $response?->withStatus(404);
        }

        if (!isset($this->request)) {
            $this->request = null;
        }
        if (!is_null($response)) {
            if (!$handler instanceof \Closure) {
                return $response->withStatus(500);
            }

            if ($middlewares !== []) {
                $response = $this->handlerMiddleware($middlewares, new RequestHandler($response, $this));
            }

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200 && $statusCode !== 302) {
                return $response;
            }
        }

        if (!$handler instanceof \Closure) {
            return $response->withStatus(500);
        }

        return (new Dispatcher($handler, $response, $params))->handle($this->request);
    }

    /**
     * @param array<int, MiddlewareInterface> $middlewares
     * @param RequestHandlerInterface $dispatcherHandler
     *
     * @return ResponseInterface
     */
    protected function handlerMiddleware(array $middlewares, RequestHandlerInterface $dispatcherHandler): ResponseInterface
    {
        foreach (array_reverse($middlewares) as $middleware) {
            $dispatcherHandler = new MiddlewareDispatcher($middleware, $dispatcherHandler);
        }

        return $dispatcherHandler->handle($this->request);
    }
}