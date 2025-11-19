<?php

declare(strict_types=1);

namespace Omegaalfa\Wrouter\Router;

use Closure;
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
use RuntimeException;


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
        if(is_null($request)){
            $request = ServerRequestFactory::fromGlobals();
        }
        $this->request = new ParsedBody()->process($request);
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
     * @return string|null
     */
    public function getCurrentGroup(): ?string
    {
        return $this->group;
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
        if (!HttpMethod::isValid($method)) {
            throw new RuntimeException('HTTP method not supported');
        }

        if ($this->group) {
            $path = $this->buildFullPath($path);
        }

        $this->addRoute($path, $handler, array_merge($this->groupMiddlewares, $this->middlewares));
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
        $params = $currentNode['params'] ?? [];

        if (is_null($currentNode) || is_null($handler)) {
            return $response?->withStatus(404);
        }

        // 1️⃣ APLICA PARAMETROS ANTES DE TUDO
        $req = $this->request;
        if ($req && is_array($params)) {
            foreach ($params as $k => $v) {
                $req = $req->withAttribute($k, $v);
            }
        }
        $this->request = $req;

        // 2️⃣ EXECUTA OS MIDDLEWARES (eles podem sobrescrever atributos)
        if ($middlewares !== []) {
            $response = $this->handlerMiddleware(
                $middlewares,
                new RequestHandler($response, $this)
            );
        }

        // 3️⃣ Se middleware gerou erro (ex: 401), retorna imediatamente
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200 && $statusCode !== 302) {
            return $response;
        }

        // 4️⃣ Finalmente executa o HANDLER
        return ($handler)($this->request, $response);
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
