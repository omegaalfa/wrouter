<?php

declare(strict_types=1);

namespace Omegaalfa\Wrouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
	/**
	 * @param  ResponseInterface  $response
	 * @param  Router             $router
	 */
	public function __construct(
		protected ResponseInterface $response,
		protected Router $router
	) { }

	/**
	 * @param  ServerRequestInterface  $request
	 *
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$this->router->setRequest($request);
		return $this->response;
	}
}