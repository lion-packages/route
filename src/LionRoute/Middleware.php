<?php

declare(strict_types=1);

namespace Lion\Route;

class Middleware
{
	public function __construct(
		private ?string $middlewareName = null,
		private ?string $class = null,
		private ?string $methodClass = null
	) {}

    public function newObject(): mixed
    {
        return new ($this->getClass())();
    }

    public function getMiddlewareName(): ?string
    {
        return $this->middlewareName;
    }

    public function setMiddlewareName(?string $middlewareName): Middleware
    {
        $this->middlewareName = $middlewareName;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(?string $class): Middleware
    {
        $this->class = $class;

        return $this;
    }

    public function getMethodClass(): ?string
    {
        return $this->methodClass;
    }

    public function setMethodClass(?string $methodClass): Middleware
    {
        $this->methodClass = $methodClass;

        return $this;
    }
}
