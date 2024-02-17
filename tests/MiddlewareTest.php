<?php

declare(strict_types=1);

namespace Tests;

use Lion\Route\Middleware;
use Lion\Test\Test;

class MiddlewareTest extends Test
{
    const MIDDLEWARE_NAME = 'custom-class';
    const MIDDLEWARE_NAME_TEST = 'custom-class-test';
    const EXAMPLE_METHOD = 'exampleMethod';
    const EXAMPLE_METHOD_TEST = 'exampleMethodTest';

    private Middleware $middleware;
    private $customClass;

    protected function setUp(): void
    {
        $this->customClass = new class {
            public function exampleMethod(): void
            {
                echo('TESTING');
            }
        };

        $this->middleware = new Middleware(self::MIDDLEWARE_NAME, $this->customClass::class, self::EXAMPLE_METHOD);
    }

    public function testConstruct(): void
    {
        $middleware = new Middleware();

        $this->assertNull($middleware->getMiddlewareName());
        $this->assertNull($middleware->getClass());
        $this->assertNull($middleware->getMethodClass());
    }

    public function testNewObject(): void
    {
        $this->assertInstanceOf($this->customClass::class, $this->middleware->newObject());
    }

    public function testGetMiddlewareName(): void
    {
        $this->assertSame(self::MIDDLEWARE_NAME, $this->middleware->getMiddlewareName());
    }

    public function testSetMiddlewareName(): void
    {
        $this->assertInstanceOf(
            $this->middleware::class,
            $this->middleware->setMiddlewareName(self::MIDDLEWARE_NAME_TEST)
        );

        $this->assertSame(self::MIDDLEWARE_NAME_TEST, $this->middleware->getMiddlewareName());
    }

    public function testGetClass(): void
    {
        $this->assertSame($this->customClass::class, $this->middleware->getClass());
    }

    public function testSetClass(): void
    {
        $newCustomClass = new class {};

        $this->assertInstanceOf($this->middleware::class, $this->middleware->setClass($newCustomClass::class));

        $this->assertSame($newCustomClass::class, $this->middleware->getClass());
    }

    public function testGetMethodClass(): void
    {
        $this->assertSame(self::EXAMPLE_METHOD, $this->middleware->getMethodClass());
    }

    public function testSetMethodClass(): void
    {
        $this->assertInstanceOf($this->middleware::class, $this->middleware->setMethodClass(self::EXAMPLE_METHOD_TEST));

        $this->assertSame(self::EXAMPLE_METHOD_TEST, $this->middleware->getMethodClass());
    }
}
