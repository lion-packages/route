<?php

declare(strict_types=1);

namespace Lion\Route;

use Closure;
use Lion\Dependency\Injection\Container;
use Lion\Route\Interface\HandlerResolverInterface;

/**
 * Class that resolves dependencies on the class's constructor
 *
 * @property Container $container [Dependency Injection Container Wrapper]
 *
 * @package Lion\Route
 */
class RouterResolver implements HandlerResolverInterface
{
    /**
     * [Dependency Injection Container Wrapper]
     *
     * @var Container $container
     */
    private Container $container;

    /**
     * Class constructor
     *
     * @param Container $container [Dependency Injection Container Wrapper]
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(array|Closure $handler): array|Closure
    {
        if (is_array($handler) && isset($handler[0]) && is_string($handler[0])) {
            $handler[0] = $this->container->resolve($handler[0]);
        }

        return $handler;
    }
}
