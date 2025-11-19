<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Router;
use Omegaalfa\Wrouter\Router\Wrouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class WrouterErrorStatusTest extends TestCase
{
    public function testDispatcherReturnsJsonWhenResponseIsNon200(): void
    {
        $request = new ServerRequest([], [], new Uri('/error'), 'GET');
        $errorResponse = (new Response())->withStatus(503);

        $router = new class($errorResponse, $request) extends Wrouter {
            public function __construct(ResponseInterface $response, ServerRequestInterface $request)
            {
                parent::__construct($response, $request);
            }

            protected function findRouteNoCached(string $path): ?ResponseInterface
            {
                return $this->response?->withStatus(503);
            }
        };

        $response = $router->dispatcher('/error');

        $this->assertNotNull($response);
        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertSame(503, $decoded['status']);
        $this->assertSame('Service Unavailable', $decoded['message']);
    }
}
