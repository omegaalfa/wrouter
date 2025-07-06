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
    public function handle(?ServerRequestInterface $request): ResponseInterface|null
    {
        return ($this->handler)($request, $this->response, $this->params);
    }
}