<?php

declare(strict_types=1);

namespace Lion\Route;

/**
 * Construct a Middleware object for web routes
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
     */
	public function __construct(
		private ?string $middlewareName = null,
		private ?string $class = null,
		private ?string $methodClass = null
	) {}

    /**
     * Create a new object of the middleware class
     *
     * @return object
     */
    public function newObject(): object
    {
        return new ($this->getClass())();
    }

    /**
     * Returns the name of the middleware
     *
     * @return string|null
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
     */
    public function setMethodClass(?string $methodClass): Middleware
    {
        $this->methodClass = $methodClass;

        return $this;
    }
}
