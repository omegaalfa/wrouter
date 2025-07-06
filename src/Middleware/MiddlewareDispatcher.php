<?php

declare(strict_types=1);

namespace Omegaalfa\Wrouter\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareDispatcher implements RequestHandlerInterface
{
    /**
     * @param MiddlewareInterface $middleware
     * @param RequestHandlerInterface $next
     */
    public function __construct(
        protected MiddlewareInterface     $middleware,
        protected RequestHandlerInterface $next,
    ){}

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middleware->process($request, $this->next);
    }
}