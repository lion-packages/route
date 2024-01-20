<?php

declare(strict_types=1);

namespace Tests\Provider;

use Lion\Route\Middleware;

class ControllerProvider
{
    private Middleware $middleware;

    public function __construct(Middleware $middleware)
    {
        $this->middleware = $middleware;
    }

    public function createMethod(): array
    {
        return [
            'status' => 'success',
            'message' => 'controller provider'
        ];
    }

    public function getMiddleware(Middleware $middleware, string $middlewareName = 'test'): array
    {
        return [
            'middleware' => $middleware->setMiddlewareName($middlewareName)->getMiddlewareName()
        ];
    }

    public function setMiddleware(string $middleware): array
    {
        return [
            'middleware' => $this->middleware->setMiddlewareName($middleware)->getMiddlewareName()
        ];
    }

    public function middleware(Middleware $middleware): array
    {
        return [
            'middleware' => $middleware->getMiddlewareName()
        ];
    }
}
