<?php

declare(strict_types=1);

namespace Tests\Provider;

use Lion\Route\Attributes\Rules;
use Tests\Provider\Rules\IdRuleProvider;
use Tests\Provider\Rules\NameRuleProvider;

class ControllerProvider
{
    public function __construct(
        private ClassProvider $classProvider,
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
    public function getMiddleware(ClassProvider $classProvider, string $middlewareName = 'test'): array
    {
        return [
            'middleware' => $classProvider->setIndex($middlewareName)->getIndex()
        ];
    }

    /**
     * @param string $middleware
     *
     * @return array{
     *     middleware: string
     * }
     */
    public function setMiddleware(string $middleware): array
    {
        return [
            'middleware' => $this->classProvider->setIndex($middleware)->getIndex()
        ];
    }

    /**
     * @return array{
     *     middleware: string
     * }
     */
    public function middleware(ClassProvider $classProvider): array
    {
        return [
            'middleware' => $classProvider->getIndex(),
        ];
    }

    /**
     * @return array{
     *     isValid: true
     * }
     */
    #[Rules(IdRuleProvider::class, NameRuleProvider::class)]
    public function testAttributes(): array
    {
        return [
            'isValid' => true,
        ];
    }
}
