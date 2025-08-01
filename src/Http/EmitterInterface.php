<?php

declare(strict_types = 1);


namespace Omegaalfa\Wrouter\Http;

use Psr\Http\Message\ResponseInterface;

interface EmitterInterface
{
	/**
	 * @param  ResponseInterface  $response
	 *
	 * @return bool
	 */
	public function emit(ResponseInterface $response): bool;
}