<?php

namespace OmegaAlfa\Wrouter\Tests;

use PHPUnit\Framework\TestCase;
use OmegaAlfa\Wrouter\Wrouter;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


class RouterTest extends TestCase
{
	/**
	 * @test
	 */
	public function it_can_add_and_find_a_route(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/users/23';

		$request = ServerRequestFactory::fromGlobals();
		$response = new Response();

		$router = new Wrouter($response);

		$router->get('/users/:id', function (RequestInterface $request, ResponseInterface $response, $params) {
			$response->getBody()->write($params[':id']);
			$response->getBody()->rewind();
			return $response;
		});

		$router->dispatcher($request);


		$this->assertEquals('23', $response->getBody()->getContents());
	}

}
