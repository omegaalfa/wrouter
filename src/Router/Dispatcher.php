<?php

declare(strict_types=1);

namespace Omegaalfa\Wrouter\Router;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Dispatcher
{

    /**
     * @param Closure $handler
     * @param ResponseInterface|null $response
     * @param mixed $params
     */
    public function __construct(
        protected Closure $handler,
        protected ?ResponseInterface $response,
        protected mixed $params
    ){}


    /**
     * @param ServerRequestInterface|null $request
     *
     * @return ResponseInterface|null
     */
    public function handle(?ServerRequestInterface $request): ?ResponseInterface
    {
        $req = $request;
        if ($req instanceof ServerRequestInterface && is_array($this->params) && count($this->params) > 0) {
            foreach ($this->params as $key => $value) {
                $req = $req->withAttribute($key, $value);
            }
        }

        $result = ($this->handler)($req, $this->response);

        if ($result instanceof ResponseInterface || $result === null) {
            return $result;
        }

        throw new \RuntimeException('Handler did not return a ResponseInterface.');
    }
}