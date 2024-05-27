<?php

declare(strict_types = 1);

namespace Omegaalfa\Router;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class Router extends TreeRouter
{
	/**
	 * @var RequestInterface
	 */
	protected RequestInterface $request;

	/**
	 * @var ResponseInterface
	 */
	protected ResponseInterface $response;

	/**
	 * @var string|null
	 */
	protected ?string $group = null;

	/**
	 * @param  RequestInterface   $request
	 * @param  ResponseInterface  $response
	 */
	public function __construct(RequestInterface $request, ResponseInterface $response)
	{
		$this->request = $request;
		$this->response = $response;
		parent::__construct();
	}

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
		return trim($prefix . trim($path, '/'), '/');
	}

	/**
	 * @param  string    $method
	 * @param  string    $path
	 * @param  callable  $handler
	 *
	 * @return void
	 */
	public function map(string $method, string $path, callable $handler): void
	{
		$method = strtoupper($method);
		if($method === $this->request->getMethod() && HttpMethod::isValid($method)) {
			$this->addRoute($this->buildFullPath($path), $handler);
		}

		if(!HttpMethod::isValid($method)) {
			throw new RuntimeException('HTTP method not supported');
		}
	}

	/**
	 * @param  string    $path
	 * @param  callable  $handler
	 *
	 * @return void
	 */
	public function get(string $path, callable $handler): void
	{
		$this->map('GET', $path, $handler);
	}

	/**
	 * @param  string    $path
	 * @param  callable  $handler
	 *
	 * @return void
	 */
	public function post(string $path, callable $handler): void
	{
		$this->map('POST', $path, $handler);
	}

	/**
	 * @param  string    $path
	 * @param  callable  $handler
	 *
	 * @return void
	 */
	public function put(string $path, callable $handler): void
	{
		$this->map('PUT', $path, $handler);
	}

	/**
	 * @param  string    $path
	 * @param  callable  $handler
	 *
	 * @return void
	 */
	public function delete(string $path, callable $handler): void
	{
		$this->map('DELETE', $path, $handler);
	}

	/**
	 * @return void
	 */
	public function run(): void
	{
		$handler = $this->findRoute($this->request->getUri()->getPath());

		if($handler instanceof \Closure) {
			$handler->call($this, $this->request, $this->response);
		}

		if(is_null($handler)) {
			http_response_code(404);
		}
	}
}
