<?php

declare(strict_types=1);

namespace Lion\Route\Interface;

use Closure;

/**
 * Defines how to resolve dependencies
 *
 * @package Lion\Route\Interface
 */
interface HandlerResolverInterface
{
    /**
     * Create an instance of the given handler
     *
     * @param array<int, mixed>|Closure $handler [Dependency to solve]
     *
     * @return array<int, mixed>|Closure
     */
    public function resolve(array|Closure $handler): array|Closure;
}
