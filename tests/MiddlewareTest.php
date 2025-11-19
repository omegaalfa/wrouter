<?php

declare(strict_types=1);

use Omegaalfa\Wrouter\Router\Wrouter;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareTest extends TestCase
{
    public function testMiddlewareOverridesRouteParams(): void
    {
        // Request simulado para o dispatcher
        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withUri(new \Laminas\Diactoros\Uri('/hello/22/marcos'));

        $router = new Wrouter(new Response(), $request);

        // variável para capturar o resultado do handler
        $captured = null;

        // registrar middleware + rota
        $router->get('/hello/:id/:name', function ($req, $res) use (&$captured) {

            // captura final dos atributos
            $captured = [
                'id'   => $req->getAttribute('id'),
                'name' => $req->getAttribute('name'),
            ];

            return $res;
        }, [
            new class implements MiddlewareInterface {
                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    // Middleware modifica "name"
                    $request = $request->withAttribute('name', 'João');
                    return $handler->handle($request);
                }
            }
        ]);

        // rodar o dispatcher
        $router->dispatcher('/hello/22/marcos');

        // ASSERTS FINAIS

        // rota gerou id=22 corretamente
        $this->assertSame('22', $captured['id']);

        // rota DEVERIA gerar name=marcos, mas middleware sobrescreve
        $this->assertSame('João', $captured['name'], 'Middleware deveria sobrescrever o valor original da rota');
    }


    public function testMiddlewareChainExecutesBeforeHandler(): void
    {
        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withUri(new \Laminas\Diactoros\Uri('/user/50'));

        $router = new Wrouter(new Response(), $request);

        $executionOrder = [];

        $router->get('/user/:id', function ($req, $res) use (&$executionOrder) {
            $executionOrder[] = 'handler';
            return $res;
        }, [

            // Middleware 1
            new class($executionOrder) implements MiddlewareInterface {
                private array $order;

                public function __construct(array &$order)
                {
                    $this->order = &$order;
                }

                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    $this->order[] = 'middleware1-before';
                    $response = $handler->handle($request);
                    $this->order[] = 'middleware1-after';
                    return $response;
                }
            },

            // Middleware 2
            new class($executionOrder) implements MiddlewareInterface {
                private array $order;

                public function __construct(array &$order)
                {
                    $this->order = &$order;
                }

                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    $this->order[] = 'middleware2-before';
                    $response = $handler->handle($request);
                    $this->order[] = 'middleware2-after';
                    return $response;
                }
            },
        ]);

        $router->dispatcher('/user/50');

        $this->assertSame([
            'middleware1-before',
            'middleware2-before',
            'middleware2-after',
            'middleware1-after',
            'handler'
        ], $executionOrder);
    }
}
