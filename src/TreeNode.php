<?php

declare(strict_types = 1);

namespace Omegaalfa\Wrouter;

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

	/**
	 * @var string|null
	 */
	public ?string $parameterName = null;

	/**
	 * @var array
	 */
	public array $middlewares = [];
}