<?php

declare(strict_types = 1);

namespace Omegaalfa\Wrouter;

use Psr\Http\Server\MiddlewareInterface;

class TreeNode
{
	/**
	 * @var array<string, TreeNode>
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
	 * @var array<int, MiddlewareInterface>
	 */
	public array $middlewares = [];
}
