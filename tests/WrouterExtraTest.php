<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Wrouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class WrouterExtraTest extends TestCase
{
    public function testPostRouteIsDispatched(): void
    {
        $request = new ServerRequest([], [], new Uri('/submit'), 'POST');
        $router = new Wrouter(new Response(), $request);

        $router->post('/submit', function (ServerRequestInterface $req, ResponseInterface $res) {
            $res->getBody()->write('posted');
            return $res->withStatus(200);
        });

        $response = $router->dispatcher('/submit');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('posted', (string) $response->getBody());
    }

    public function testDispatcherReturnsJsonErrorForNotFound(): void
    {
        $request = new ServerRequest([], [], new Uri('/missing'), 'GET');
        $router = new Wrouter(new Response(), $request);

        $response = $router->dispatcher('/missing');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        $this->assertIsArray($decoded);
        $this->assertSame(404, $decoded['status'] ?? null);
        $this->assertSame('Not Found', $decoded['message'] ?? null);
    }

    public function testEmitResponseOutputsBody(): void
    {
        $router = new Wrouter();

        $response = (new Response())
            ->withStatus(200)
            ->withHeader('Content-Type', 'text/plain');
        $response->getBody()->write('emit-body');

        ob_start();
        $result = $router->emitResponse($response);
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertSame('emit-body', $output);
    }
}
