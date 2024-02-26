<?php

declare(strict_types=1);

namespace Lion\Route;

use DI\Container;
use Phroute\Phroute\HandlerResolverInterface;

/**
 * Class that resolves dependencies on the class's constructor
 *
 * @package Lion\Route
 */
class RouterResolver implements HandlerResolverInterface
{
    /**
     * [Container class object]
     *
     * @var Container $container
     */
    private Container $container;

    /**
     * Class constructor
     *
     * @param Container $container [Container class object]
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Resolves the dependencies of the constructor method of the class being
     * invoked
     *
     * @param  mixed $handler
     */
    public function resolve($handler)
    {
        if(is_array($handler) and is_string($handler[0])) {
            $handler[0] = $this->container->get($handler[0]);
        }

        return $handler;
    }
}
