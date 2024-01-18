<?php

declare(strict_types=1);

namespace Lion\Route;

class Middleware
{
	public function __construct(
		private ?string $middlewareName = null,
		private ?string $objectClass = null,
		private ?string $methodClass = null
	) {}

    public function newObject(): mixed
    {
        $objectClass = $this->getObjectClass();

        return new $objectClass();
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

    public function getObjectClass(): ?string
    {
        return $this->objectClass;
    }

    public function setObjectClass(?string $objectClass): Middleware
    {
        $this->objectClass = $objectClass;

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
