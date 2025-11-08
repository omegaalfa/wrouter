<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Omegaalfa\Wrouter\Router\Wrouter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$request = ServerRequestFactory::fromGlobals();
$response = new Response();

$router = new Wrouter($response, $request);

// Exemplo de rotas reais


$router->get('/users/:id', function (ServerRequestInterface $req, ResponseInterface $res) {
    $res->getBody()->write('User ID');
    return $res->withStatus(200);
});

$router->get('/hello/word', function (ServerRequestInterface $req, ResponseInterface $res) {
    $res->getBody()->write('Hello World');
    return $res->withStatus(200);
});

$router->get('/test/middleware', function (ServerRequestInterface $req, ResponseInterface $res) {
    $res->getBody()->write('Routing Test Middleware');
    return $res->withStatus(200);
}, [
    new class implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler): ResponseInterface
        {
            $response = $handler->handle($request);
            $response->getBody()->write("\n[Middleware passou]");
            $response = $response->withStatus(200);
            return $response;
        }
    }
]);

$router->get('/products/:id/details/:new', function (ServerRequestInterface $req, ResponseInterface $res) {
    $res->getBody()->write('Product Details');
    return $res->withStatus(200);
});

// Dispatcher
$path = $request->getUri()->getPath();
$response = $router->dispatcher($path);

// Emite a resposta HTTP
$router->emitResponse($response);
