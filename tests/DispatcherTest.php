<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Omegaalfa\Wrouter\Router\Dispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DispatcherTest extends TestCase
{
    public function testHandleReturnsResponseFromHandler(): void
    {
        $response = new Response();
        $dispatcher = new Dispatcher(
            function (ServerRequestInterface $request, ResponseInterface $res): ResponseInterface {
                $res->getBody()->write('ok');
                return $res->withStatus(201);
            },
            $response,
            ['foo' => 'bar']
        );

        $result = $dispatcher->handle(new ServerRequest());

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(201, $result->getStatusCode());
        $this->assertSame('ok', (string) $result->getBody());
    }

    public function testHandleAllowsNullResponseFromHandler(): void
    {
        $dispatcher = new Dispatcher(
            function (): null {
                return null;
            },
            null,
            null
        );

        $result = $dispatcher->handle(null);
        $this->assertNull($result);
    }

    public function testHandleThrowsWhenHandlerReturnsInvalidType(): void
    {
        $dispatcher = new Dispatcher(
            function (): string {
                return 'not-a-response';
            },
            null,
            null
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Handler did not return a ResponseInterface.');

        $dispatcher->handle(new ServerRequest());
    }

    public function testDispatcherDoesNotApplyEmptyParams(): void
    {
        $response = new Response();

        $dispatcher = new Dispatcher(
            function (ServerRequestInterface $request, ResponseInterface $response, ?array $params = null) {
                $this->assertNull($params);
                $response->getBody()->write('ok');
                return $response;
            },
            $response,
            null
        );

        $req = new ServerRequest([], [], new \Laminas\Diactoros\Uri('/x'), 'GET');
        $res = $dispatcher->handle($req);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('ok', (string) $res->getBody());
    }

}
