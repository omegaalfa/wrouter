<?php

namespace Omegaalfa\Wrouter\Router;

use ReflectionException;
use ReflectionFunction;

class RouterCache
{

	/**
	 * @var string
	 */
	protected string $cachePath = __DIR__ . '/cache/cache_routes.php';

}