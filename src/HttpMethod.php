<?php

declare(strict_types = 1);

namespace Omegalfa\Router;

/**
 * Enumeração dos métodos HTTP válidos.
 */
enum HttpMethod
{
	/**
	 * @return string[]
	 */
	public static function getAll(): array
	{
		return ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];
	}


	/**
	 * @param  string  $method
	 *
	 * @return bool
	 */
	public static function isValid(string $method): bool
	{
		return in_array($method, self::getAll(), true);
	}
}