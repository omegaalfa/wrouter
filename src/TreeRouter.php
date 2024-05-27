<?php

declare(strict_types = 1);

namespace Omegalfa\Router;


class TreeRouter
{
	/**
	 * @var TreeNode
	 */
	private TreeNode $root;


	public function __construct()
	{
		$this->root = new TreeNode();
	}

	/**
	 * @param  string       $path
	 * @param  callable     $handler
	 * @param  string|null  $group
	 *
	 * @return void
	 */
	protected function addRoute(string $path, callable $handler, string $group = null): void
	{
		$currentNode = $this->root;
		foreach(explode('/', trim($path, '/')) as $segment) {
			if(empty($segment)) {
				continue;
			}

			if(!isset($currentNode->children[$segment])) {
				$currentNode->children[$segment] = new TreeNode();
			}

			$currentNode = $currentNode->children[$segment];
		}

		$currentNode->isEndOfRoute = true;
		$currentNode->handler = $handler;
	}


	/**
	 * @param  string  $path
	 *
	 * @return mixed
	 */
	protected function findRoute(string $path): mixed
	{
		$currentNode = $this->root;
		$parts = explode('/', trim($path, '/'));

		foreach($parts as $segment) {
			if(empty($segment)) {
				continue;
			}
			if(!isset($currentNode->children[$segment])) {
				return null;
			}

			$currentNode = $currentNode->children[$segment];
		}

		return $currentNode->isEndOfRoute ? $currentNode->handler : null;
	}

}