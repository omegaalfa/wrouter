<?php

declare(strict_types = 1);

namespace Omegaalfa\Wrouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;


class Wrouter extends Router
{

	/**
	 * @var array<int, MiddlewareInterface>
	 */
	protected array $middlewares = [];

	/**
	 * @var string|null
	 */
	protected ?string $group = null;

	/**
	 * @var array<int, MiddlewareInterface>
	 */
	protected array $groupMiddlewares = [];

	/**
	 * @param  string                           $prefix
	 * @param  callable                         $callback
	 * @param  array<int, MiddlewareInterface>  $middlewares
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
	 * @param  string  $path  The path for the route.
	 *
	 * @return string The full path including the group prefix (if any).
	 */
	private function buildFullPath(string $path): string
	{
		$prefix = $this->group ? '/' . $this->group . '/' : '';
		if(empty($prefix)) {
			return $path;
		}
		return trim($prefix . trim($path, '/'), '/');
	}

	/**
	 * @param  string    $method
	 * @param  string    $path
	 * @param  callable  $handler
	 *
	 * @return void
	 */
	protected function map(string $method, string $path, callable $handler): void
	{
		$method = strtoupper($method);
		if(!HttpMethod::isValid($method)) {
			throw new \RuntimeException('HTTP method not supported');
		}

		if($this->group) {
			$path = $this->buildFullPath($path);
		}

		$this->addRoute($path, $handler, array_merge($this->groupMiddlewares, $this->middlewares));
	}

	/**
	 * @param  string                           $path
	 * @param  callable                         $handler
	 * @param  array<int, MiddlewareInterface>  $middlewares
	 *
	 * @return $this
	 */
	public function get(string $path, callable $handler, array $middlewares = []): static
	{
		$this->middlewares = $middlewares;
		$this->map('GET', $path, $handler);

		return $this;
	}

	/**
	 * @param  string                           $path
	 * @param  callable                         $handler
	 * @param  array<int, MiddlewareInterface>  $middlewares
	 *
	 * @return $this
	 */
	public function post(string $path, callable $handler, array $middlewares = []): static
	{
		$this->middlewares = $middlewares;
		$this->map('POST', $path, $handler);

		return $this;
	}

	/**
	 * @param  string                           $path
	 * @param  callable                         $handler
	 * @param  array<int, MiddlewareInterface>  $middlewares
	 *
	 * @return $this
	 */
	public function put(string $path, callable $handler, array $middlewares = []): static
	{
		$this->middlewares = $middlewares;
		$this->map('PUT', $path, $handler);

		return $this;
	}

	/**
	 * @param  string                           $path
	 * @param  callable                         $handler
	 * @param  array<int, MiddlewareInterface>  $middlewares
	 *
	 * @return $this
	 */
	public function delete(string $path, callable $handler, array $middlewares = []): static
	{
		$this->middlewares = $middlewares;
		$this->map('DELETE', $path, $handler);

		return $this;
	}


	/**
	 * @param  ServerRequestInterface  $request
	 *
	 * @return ResponseInterface
	 */
	public function dispatcher(ServerRequestInterface $request): ResponseInterface
	{
		return $this->findRouteNoCached($request);
	}
}
