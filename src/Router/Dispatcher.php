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
     * @return ?ResponseInterface
     */
    public function handle(?ServerRequestInterface $request): ?ResponseInterface
    {
        $result = ($this->handler)($request, $this->response, $this->params);

        if ($result instanceof ResponseInterface || $result === null) {
            return $result;
        }

        throw new \RuntimeException('Handler did not return a ResponseInterface.');
    }
}