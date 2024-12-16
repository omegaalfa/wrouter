<?php

declare(strict_types = 1);


namespace Omegaalfa\Wrouter\emit;

use Psr\Http\Message\ResponseInterface;


class Emitter implements EmitterInterface
{
	use EmitterTrait;

	/**
	 * Emits a response for a PHP SAPI environment.
	 *
	 * Emits the status line and headers via the header() function, and the
	 * body content via the output buffer.
	 *
	 * @param  ResponseInterface  $response
	 *
	 * @return bool
	 */
	public function emit(ResponseInterface $response): bool
	{
		$this->emitHeaders($response);
		$this->emitStatusLine($response);
		$this->emitBody($response);

		return true;
	}

	/**
	 * Emit the message body.
	 *
	 * @param  ResponseInterface  $response
	 *
	 * @return void
	 */
	private function emitBody(ResponseInterface $response): void
	{
		echo $response->getBody();
	}
}