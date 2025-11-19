<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Wrouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class WrouterRequestSwitchTest extends TestCase
{
    public function testSetRequestAllowsReusingRouterForDifferentPaths(): void
    {
        $router = new Wrouter();

        $router->setRequest(new ServerRequest([], [], new Uri('/first'), 'GET'));
        $router->get('/first', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('first');
            return $response->withStatus(200);
        });

        $router->setRequest(new ServerRequest([], [], new Uri('/second'), 'GET'));
        $router->get('/second', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('second');
            return $response->withStatus(200);
        });

        $router->setRequest(new ServerRequest([], [], new Uri('/first'), 'GET'));
        $first = $router->dispatcher('/first');
        $this->assertSame('first', (string) $first->getBody());

        $router->setRequest(new ServerRequest([], [], new Uri('/second'), 'GET'));
        $ref = new \ReflectionProperty(Wrouter::class, 'response');
        $ref->setAccessible(true);
        $ref->setValue($router, new Response());
        $second = $router->dispatcher('/second');
        $this->assertSame('second', (string) $second->getBody());
    }
}
