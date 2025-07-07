# Wrouter

**Wrouter** é um roteador HTTP moderno, minimalista e poderoso, construído em PHP com suporte a Middlewares PSR-15, cache de rotas, suporte a grupos, métodos HTTP padronizados e parsing automático de corpo para JSON, XML e form-url-encoded. É altamente extensível e ideal para aplicações web ou APIs.

---

## ✨ Recursos Principais

- Roteamento baseado em árvore (TreeRouter)
- Suporte a grupos de rotas com prefixo
- Middlewares com cadeia compatível com PSR-15
- Manipulação automática de corpo de requisição (`application/json`, `form-urlencoded`, `xml`)
- Emissor de resposta compatível com PHP SAPI
- Cache e serialização de rotas
- Compatível com PSR-7 (`psr/http-message`)
- Tipagem estrita (`strict_types=1`) com suporte ao PHP 8.1+

---

## 🚀 Instalação

Use o Composer para instalar:

```bash
composer require omegaalfa/wrouter
```

---

## 🔧 Exemplo de Uso

```php
use Omegaalfa\Wrouter\Router\Wrouter;
use Laminas\Diactoros\Response\JsonResponse;

$router = new Wrouter();

$router->get('/hello', function ($request, $response) {
    return new JsonResponse(['message' => 'Hello World']);
});

$router->dispatcher('/hello');
```

---

## 🧠 Anatomia do Roteador

### Métodos suportados

- `GET`, `POST`, `PUT`, `DELETE`, `PATCH`, `OPTIONS`, `HEAD`, `TRACE`, `CONNECT`

### Registrando rotas

```php
$router->get('/path', $handler, [$middleware1, $middleware2]);
$router->post('/submit', $handler);
```

### Grupos com prefixo

```php
$router->group('/api', function($r) {
    $r->get('/users', $handler);
    $r->post('/users', $handler);
}, [$authMiddleware]);
```

---

## 🧩 Middlewares

Middlewares devem implementar `Psr\Http\Server\MiddlewareInterface`:

```php
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class AuthMiddleware implements MiddlewareInterface {
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        // Verificação de autenticação aqui
        return $handler->handle($request);
    }
}
```

---

## 📦 Cache de Rotas

Você pode gerar rotas em cache para melhorar desempenho:

```php
$routerCache = new RouterCache();
$routerCache->generateRoutes('/api/user/{id}', 'GET', function($req, $res) {
    // Handler da rota
});
```

O arquivo gerado (`cache_routes.php`) conterá rotas serializadas para carregamento posterior.

---

## 🧪 Parsing de Corpo

O corpo da requisição é automaticamente analisado com base no `Content-Type`:

- `application/json`
- `application/x-www-form-urlencoded`
- `application/xml` ou `text/xml`

Acesse o corpo analisado com:

```php
$request->getParsedBody();
```

---

## 🛠️ Emissão de Resposta

O roteador pode emitir a resposta diretamente para o cliente:

```php
$response = $router->dispatcher('/hello');
$router->emitResponse($response);
```

---

## 📁 Estrutura do Projeto

```
src/
├── Http/
│   ├── Emitter.php
│   ├── HttpMethod.php
│   └── RequestHandler.php
├── Middleware/
│   └── MiddlewareDispatcher.php
├── Router/
│   ├── Router.php
│   ├── Wrouter.php
│   ├── TreeRouter.php
│   ├── TreeNode.php
│   ├── Dispatcher.php
│   └── RouterCache.php
└── Support/
    └── ParsedBody.php
```

---

## 📜 Requisitos

- PHP 8.1 ou superior
- PSR-7 (`psr/http-message`)
- PSR-15 para middlewares

---

## 🤝 Contribuindo

Sinta-se à vontade para abrir issues ou pull requests.
Sugestões, correções e melhorias são bem-vindas!

---

## 🪪 Licença

MIT © [Omegaalfa](https://github.com/omegaalfa)
