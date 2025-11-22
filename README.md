# 🚀 Wrouter

**Wrouter** é um roteador HTTP moderno, minimalista e ultrarrápido, construído em PHP com suporte a **Middlewares PSR-15**, **cache inteligente de rotas**, **suporte a grupos**, **métodos HTTP padronizados** e **parsing automático de corpo** para JSON, XML e `form-url-encoded`.  
É **altamente extensível** e ideal para aplicações web, APIs REST e microsserviços.

---

## 📊 Benchmark

Teste realizado com **3.5 milhões de requisições** contra diferentes tipos de rotas:

| Cenário                  | Wrouter     | Jaunt       | Symfony     | Phroute     | Slim        |
|--------------------------|-------------|-------------|-------------|-------------|-------------|
| Rota simples             | **5.86 μs** | 7.11 μs     | 6.78 μs     | 7.64 μs     | 32.36 μs    |
| Rota estática curta      | **6.07 μs** ⭐ | 7.96 μs     | 6.89 μs     | 7.70 μs     | 32.41 μs    |
| Rota dinâmica (1 param)  | **8.02 μs** ⭐ | 9.21 μs     | 11.12 μs    | 12.98 μs    | 40.72 μs    |
| Rota dinâmica (2 params) | **8.47 μs** ⭐ | 10.55 μs    | 17.00 μs    | 78.34 μs    | 112.63 μs   |
| Rota estática profunda   | **6.10 μs** ⭐ | 9.63 μs     | 6.78 μs     | 7.59 μs     | 32.73 μs    |

> ✅ **Wrouter é o líder em 4 dos 5 cenários**, com latência até **14x menor** que o Slim Framework em rotas complexas.

---

## ✨ Recursos Principais

- 🌳 **Roteamento em Árvore Otimizado**: `TreeRouter` com busca **O(1)** para rotas estáticas
- ⚡ **Cache Inteligente LRU**: Reduz *overhead* de rotas dinâmicas repetidas
- 🎯 **Suporte a Grupos**: Organize rotas com prefixos e middlewares compartilhados
- 🔗 **Middlewares PSR-15**: Cadeia compatível com padrão **PSR-15**
- 📦 **Parsing Automático**: JSON, XML e `form-url-encoded` *out-of-the-box*
- 🛡️ **Tipagem Estrita**: Requer **PHP 8.1+** com `strict_types=1`
- ♻️ **Cache e Serialização**: Persistência de rotas compiladas
- 📄 **PSR-7 Compatível**: Funciona com qualquer implementação **PSR-7**
- 🚀 **Zero Dependências Obrigatórias**: Apenas `laminas/diactoros` (substituível)

---

## 📋 Requisitos

- PHP 8.4
- `psr/http-message` (PSR-7)
- `psr/http-server-middleware` (PSR-15)
- Implementação PSR-7 (ex: `laminas/diactoros`)

---

## 🔧 Instalação

Use o Composer:

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

$response = $router->dispatcher('/hello');
$router->emitResponse($response);
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

- PHP 8.4 ou superior
- PSR-7 (`psr/http-message`)
- PSR-15 para middlewares

---

## 🤝 Contribuindo

Sinta-se à vontade para abrir issues ou pull requests.
Sugestões, correções e melhorias são bem-vindas!

---

## 🪪 Licença

MIT © [Omegaalfa](https://github.com/omegaalfa)
