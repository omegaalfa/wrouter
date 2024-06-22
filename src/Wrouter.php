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
	 * @param  string    $prefix
	 * @param  callable  $callback
	 *
	 * @return void
	 */
	public function group(string $prefix, callable $callback): void
	{
		$previousGroup = $this->group;
		$this->group = trim($prefix, '/');
		$callback($this);
		$this->group = $previousGroup;
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
	 * @param  string                           $method
	 * @param  string                           $path
	 * @param  callable                         $handler
	 * @param  array<int, MiddlewareInterface>  $middlewares
	 *
	 * @return void
	 */
	public function map(string $method, string $path, callable $handler, array $middlewares = []): void
	{
		$method = strtoupper($method);
		if(!HttpMethod::isValid($method)) {
			throw new \RuntimeException('HTTP method not supported');
		}

		if($this->group) {
			$path = $this->buildFullPath($path);
		}

		$this->addRoute($path, $handler, $middlewares);
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
		$this->map('GET', $path, $handler, $middlewares);

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
		$this->map('POST', $path, $handler, $middlewares);

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
		$this->map('PUT', $path, $handler, $middlewares);

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
		$this->map('DELETE', $path, $handler, $middlewares);

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
