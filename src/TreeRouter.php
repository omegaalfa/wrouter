<?php

declare(strict_types = 1);

namespace Omegaalfa\Wrouter;

use Psr\Http\Server\MiddlewareInterface;

class TreeRouter
{
	/**
	 * @var TreeNode
	 */
	protected TreeNode $root;

	/**
	 * @var ?array<string, int>
	 */
	protected array|null $parametersPath = null;

	/**
	 * @var ?array<mixed, string>
	 */
	protected array|null $parameters = null;


	public function __construct()
	{
		$this->root = new TreeNode();
	}

	/**
	 * @param  string                           $path
	 * @param  callable                         $handler
	 * @param  array<int, MiddlewareInterface>  $middlewares
	 *
	 * @return void
	 */
	protected function addRoute(string $path, callable $handler, array $middlewares = []): void
	{
		$currentNode = $this->root;
		$parts = explode('/', trim($path, '/'));

		foreach($parts as $key => $segment) {
			if(empty($segment)) {
				continue;
			}
			if(str_contains($segment, ':')) {
				$this->parameters[$key] = $segment;
				$this->parametersPath[":parameter{$key}"] = $key;
				$segment = ":parameter{$key}";
			}

			if(!isset($currentNode->children[$segment])) {
				$currentNode->children[$segment] = new TreeNode();
			}
			$currentNode = $currentNode->children[$segment];
		}

		$currentNode->isEndOfRoute = true;
		$currentNode->handler = $handler;
		$currentNode->middlewares = $middlewares;
	}


	/**
	 * @param  string  $path
	 *
	 * @return ?array<string, mixed>
	 */
	protected function findRoute(string $path): ?array
	{
		$currentNode = $this->root;
		$parts = explode('/', trim($path, '/'));
		$paths = $parts;
		$paramPaths = [];
		if(is_array($this->parametersPath)) {
			foreach($this->parametersPath as $key => $value) {
				if(str_contains($key, ':parameter')) {
					$parts[$value] = $key;
					if(isset($paths[$value])) {
						$paramPaths[$value] = $paths[$value];
					}
				}
			}
		}

		if(count($paths) !== count($parts)) {
			$parts = $paths;
		}

		if(is_array($this->parameters) && (count($this->parameters) === count($paramPaths))) {
			$this->parameters = array_combine($this->parameters, $paramPaths);
		}

		foreach($parts as $segment) {
			if(empty($segment)) {
				continue;
			}
			if(!isset($currentNode->children[$segment])) {
				return null;
			}

			$currentNode = $currentNode->children[$segment];
		}

		if($currentNode->isEndOfRoute) {
			return [
				'handler'     => $currentNode->handler,
				'parameters'  => $this->parameters,
				'middlewares' => $currentNode->middlewares,
			];
		}

		return null;
	}
}
