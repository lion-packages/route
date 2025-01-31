<?php

declare(strict_types=1);

namespace Tests\Provider;

use Lion\Route\Attributes\Rules;
use Lion\Route\Middleware;
use Tests\Provider\Rules\IdRuleProvider;
use Tests\Provider\Rules\NameRuleProvider;

class ControllerProvider
{
    public function __construct(
        private Middleware $middleware,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function createMethod(): array
    {
        return [
            'status' => 'success',
            'message' => 'controller provider'
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function getMiddleware(Middleware $middleware, string $middlewareName = 'test'): array
    {
        return [
            'middleware' => $middleware->setMiddlewareName($middlewareName)->getMiddlewareName()
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function setMiddleware(string $middleware): array
    {
        return [
            'middleware' => $this->middleware->setMiddlewareName($middleware)->getMiddlewareName()
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function middleware(Middleware $middleware): array
    {
        return [
            'middleware' => $middleware->getMiddlewareName(),
        ];
    }

    /**
     * @return array<string, bool>
     */
    #[Rules(IdRuleProvider::class, NameRuleProvider::class)]
    public function testAttributes(): array
    {
        return [
            'isValid' => true,
        ];
    }
}
