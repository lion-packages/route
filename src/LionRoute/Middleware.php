<?php

declare(strict_types=1);

namespace Lion\Route;

/**
 * Construct a Middleware object for web routes
 *
 * @property string|null $middlewareName [Middleware name]
 * @property string|null $class [Class that invokes the middleware]
 * @property string|null $methodClass [Name of the method that is invoked in the
 * middleware class]
 * @property array<string, mixed>|null $params [List of defined parameters]
 *
 * @package Lion\Route
 */
class Middleware
{
    /**
     * Class constructor
     *
     * @param string|null $middlewareName [Middleware name]
     * @param string|null $class [Class that invokes the middleware]
     * @param string|null $methodClass [Name of the method that is invoked in
     * the middleware class]
     * @param array<string, mixed>|null $params [List of defined parameters]
     */
    public function __construct(
        private ?string $middlewareName = null,
        private ?string $class = null,
        private ?string $methodClass = null,
        private ?array $params = []
    ) {
    }

    /**
     * Returns the name of the middleware
     *
     * @return string|null
     *
     * @internal
     */
    public function getMiddlewareName(): ?string
    {
        return $this->middlewareName;
    }

    /**
     * Rename the middleware
     *
     * @param string|null $middlewareName [Middleware name]
     *
     * @return Middleware
     *
     * @internal
     */
    public function setMiddlewareName(?string $middlewareName): Middleware
    {
        $this->middlewareName = $middlewareName;

        return $this;
    }

    /**
     * Returns the namespace of the class being invoked
     *
     * @return string|null
     *
     * @internal
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * Change the namespace of the class that is invoked
     *
     * @param string|null $class [Class that invokes the middleware]
     *
     * @return Middleware
     *
     * @internal
     */
    public function setClass(?string $class): Middleware
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Returns the method that is invoked from the middleware class
     *
     * @return string|null
     *
     * @internal
     */
    public function getMethodClass(): ?string
    {
        return $this->methodClass;
    }

    /**
     * Change the name of the method that is invoked from the middleware class
     *
     * @param string|null $methodClass [Name of the method that is invoked in
     * the middleware class]
     *
     * @return Middleware
     *
     * @internal
     */
    public function setMethodClass(?string $methodClass): Middleware
    {
        $this->methodClass = $methodClass;

        return $this;
    }

    /**
     * Returns the list of parameters defined for the middleware
     *
     * @return array<string, mixed>|null
     *
     * @internal
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    /**
     * Change the parameters of the method that is invoked from the middleware
     * class
     *
     * @param array<string, mixed>|null $params [List of defined parameters]
     *
     * @return Middleware
     *
     * @internal
     */
    public function setParams(array $params): Middleware
    {
        $this->params = $params;

        return $this;
    }
}
