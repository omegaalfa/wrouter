<?php

declare(strict_types = 1);

namespace Omegaalfa\Router;

class TreeNode
{
	/**
	 * @var array
	 */
	public array $children = [];

	/**
	 * @var bool
	 */
	public bool $isEndOfRoute = false;

	/**
	 * @var mixed|null
	 */
	public mixed $handler = null;
}
