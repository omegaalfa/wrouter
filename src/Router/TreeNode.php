<?php

declare(strict_types=1);

namespace Omegaalfa\Wrouter\Router;

use Psr\Http\Server\MiddlewareInterface;

class TreeNode
{
    /** @var array<string, TreeNode> */
    public array $children = [];

    /** @var array<int, MiddlewareInterface> */
    public array $middlewares = [];

    /** @var bool */
    public bool $isEndOfRoute = false;

    /**@var mixed|null */
    public mixed $handler = null;
}