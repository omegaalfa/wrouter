<?php

declare(strict_types = 1);

namespace Omegaalfa\Wrouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Router extends TreeRouter
{

	/**
	 * @var ResponseInterface
	 */
	protected ResponseInterface $response;


	/**
	 * @param  ResponseInterface  $response
	 */
	public function __construct(ResponseInterface $response)
	{
		$this->response = $response;
		parent::__construct();
	}

	/**
	 * @param  ServerRequestInterface  $request
	 *
	 * @return ResponseInterface
	 */
	protected function findRouteNoCached(ServerRequestInterface $request): ResponseInterface
	{
		$currentNode = $this->findRoute($request->getUri()->getPath());
		$response = $this->response;
		$handler = $currentNode['handler'] ?? null;
		$middlewares = $currentNode['middlewares'] ?? [];
		$params = $currentNode['parameters'] ?? [];

		if(is_null($currentNode) || is_null($handler)) {
			http_response_code(404);
			return $response;
		}

		if(!$handler instanceof \Closure) {
			return $response;
		}

		$dispatcherHandler = new Dispatcher($handler, $response, $params);
		if(is_array($middlewares) && $middlewares) {
			foreach(array_reverse($middlewares) as $middleware) {
				$dispatcherHandler = new MiddlewareDispatcher($middleware, $dispatcherHandler);
			}

			return $dispatcherHandler->handle($request);
		}

		return $dispatcherHandler->handle($request);
	}

}
