<?php

declare(strict_types = 1);

namespace Omegaalfa\Wrouter;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Dispatcher implements RequestHandlerInterface
{

	/**
	 * @param  Closure            $handler
	 * @param  ResponseInterface  $response
	 * @param  mixed              $params
	 */
	public function __construct(protected Closure $handler, protected ResponseInterface $response, protected mixed $params) { }


	/**
	 * @param  ServerRequestInterface  $request
	 *
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return call_user_func($this->handler, $request, $this->response, $this->params);
	}
}