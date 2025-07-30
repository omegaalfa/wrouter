<?php

declare(strict_types=1);

namespace Omegaalfa\Wrouter\Router;


use JsonException;
use Omegaalfa\Wrouter\Http\Emitter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;


class Wrouter extends Router
{
    /**
     * @param string $path
     * @param callable $handler
     * @param array<int, MiddlewareInterface> $middlewares
     *
     * @return $this
     */
    public function get(string $path, callable $handler, array $middlewares = []): static
    {
        $this->addMiddleware($middlewares);
        $this->map('GET', $path, $handler);

        return $this;
    }

    /**
     * @param string $path
     * @param callable $handler
     * @param array<int, MiddlewareInterface> $middlewares
     *
     * @return $this
     */
    public function post(string $path, callable $handler, array $middlewares = []): static
    {
        $this->addMiddleware($middlewares);
        $this->map('POST', $path, $handler);

        return $this;
    }

    /**
     * @param string $path
     * @param callable $handler
     * @param array<int, MiddlewareInterface> $middlewares
     *
     * @return $this
     */
    public function put(string $path, callable $handler, array $middlewares = []): static
    {
        $this->addMiddleware($middlewares);
        $this->map('PUT', $path, $handler);

        return $this;
    }

    /**
     * @param string $path
     * @param callable $handler
     * @param array<int, MiddlewareInterface> $middlewares
     *
     * @return $this
     */
    public function delete(string $path, callable $handler, array $middlewares = []): static
    {
        $this->addMiddleware($middlewares);
        $this->map('DELETE', $path, $handler);

        return $this;
    }


    /**
     * @param array<int, MiddlewareInterface> $middlewares
     *
     * @return void
     */
    private function addMiddleware(array $middlewares): void
    {
        $this->middlewares = [];
        if ($middlewares) {
            $this->middlewares = array_merge($this->middlewares, $middlewares);
        }
    }

    /**
     * @param ResponseInterface $response
     *
     * @return bool
     */
    public function emitResponse(ResponseInterface $response): bool
    {
        return (new Emitter())->emit($response);
    }


    /**
     * @param string $path
     *
     * @return ?ResponseInterface
     */
    public function dispatcher(string $path): ?ResponseInterface
    {
        $response = $this->findRouteNoCached($path);

        if ($response?->getStatusCode() === 200) {
            return $response;
        }

        try {
            $response = $response?->withHeader('Content-Type', 'application/json');
            $response?->getBody()->write(json_encode([
                'status' => $response->getStatusCode(),
                'message' => $response->getReasonPhrase()
            ], JSON_THROW_ON_ERROR));
        } catch (JsonException $e) {
            $response?->getBody()->write($e->getMessage());
        }

        return $response;
    }
}