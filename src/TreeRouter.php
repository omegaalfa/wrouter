<?php

declare(strict_types = 1);

namespace Omegaalfa\Wrouter;


class TreeRouter
{
	/**
	 * @var TreeNode
	 */
	protected TreeNode $root;

	/**
	 * @var array|null
	 */
	protected array|null $parametersPath = null;


	public function __construct()
	{
		$this->root = new TreeNode();
	}

	/**
	 * @param  string    $path
	 * @param  callable  $handler
	 * @param  array     $middlewares
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
			if(str_contains($segment, '{') && str_contains($segment, '}')) {
				$this->parametersPath[':parameter'] = $key;
				$segment = ':parameter';
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
	 * @return array|null
	 */
	protected function findRoute(string $path): ?array
	{
		$currentNode = $this->root;
		$parts = explode('/', trim($path, '/'));
		$paths = $parts;

		if(isset($this->parametersPath[':parameter']) && $key = $this->parametersPath[':parameter']) {
			$parts[$key] = ':parameter';
		}

		if(count($paths) !== count($parts)) {
			$parts = $paths;
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
				'middlewares' => $currentNode->middlewares,
			];
		}

		return null;
	}

}
