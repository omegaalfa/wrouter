# Wrouter

wrouter is a high-performance routing library implemented using Trie data structures. Designed for PHP 8, it focuses on speed and efficiency, making it suitable for large-scale applications.

## Installation

```bash
composer require omegaalfa/wrouter
```

# Laminas\Diactoros
wrouter uses Laminas\Diactoros for PSR-7 HTTP message implementations. Laminas\Diactoros is a standard PHP library for PSR-7 that provides HTTP messages (requests and responses), stream interfaces, and utilities for working with these messages. This ensures compatibility with a wide range of middleware and frameworks adhering to PSR-7 and PSR-15.

# Prerequisites

PHP 8.1 or higher

# Features
- Trie-based routing: Efficient route matching with minimal overhead.
- Middleware support: Easily add and manage middleware for your routes.
- Parameter handling: Supports dynamic route parameters.
- PSR-7 and PSR-15 compatible: Works seamlessly with PSR-7 HTTP message interfaces and PSR-15 middleware interfaces.

# Examples

```php
use OmegaAlfa\Wrouter\Router;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

$request = ServerRequestFactory::fromGlobals();
$response = new Response();

$router = new Wrouter($response);

// Add a route
$router->get('/users/:id', function (RequestInterface $request, ResponseInterface $response, $params) {
    // Route handler logic
    echo "User ID:" . $params[':id'];
    return $response;
});

$router->get('/admin', function (RequestInterface $request, ResponseInterface $response) {
    // Route handler logic
    return $response;
}, [new \src\router\src\LoggingMiddleware]);

$router->dispatcher($request);
```

# Contributing
Feel free to submit issues or pull requests. For major changes, please open an issue first to discuss what you would like to change.

# License
This project is licensed under the MIT License. See the LICENSE file for details.
