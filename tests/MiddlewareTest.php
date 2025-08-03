<?php

declare(strict_types=1);

namespace Tests;

use Lion\Route\Middleware;
use Lion\Test\Test;

class MiddlewareTest extends Test
{
    public const string MIDDLEWARE_NAME = 'custom-class';
    public const string MIDDLEWARE_NAME_TEST = 'custom-class-test';
    public const string EXAMPLE_METHOD = 'exampleMethod';
    public const string EXAMPLE_METHOD_TEST = 'exampleMethodTest';
    public const array EXAMPLE_PARAMS = ['key' => 'value'];
    public const array EXAMPLE_PARAMS_TEST = ['key' => 'value2'];

    private Middleware $middleware;
    private $customClass;

    protected function setUp(): void
    {
        $this->customClass = new class () {
            public function exampleMethod(): void
            {
                echo('TESTING');
            }
        };

        $this->middleware = new Middleware(
            self::MIDDLEWARE_NAME,
            $this->customClass::class,
            self::EXAMPLE_METHOD,
            self::EXAMPLE_PARAMS
        );
    }

    public function testConstruct(): void
    {
        $middleware = new Middleware();

        $this->assertNull($middleware->getMiddlewareName());
        $this->assertNull($middleware->getClass());
        $this->assertNull($middleware->getMethodClass());
    }

    public function testGetMiddlewareName(): void
    {
        $this->assertSame(self::MIDDLEWARE_NAME, $this->middleware->getMiddlewareName());
    }

    public function testSetMiddlewareName(): void
    {
        $this->assertInstanceOf(Middleware::class, $this->middleware->setMiddlewareName(self::MIDDLEWARE_NAME_TEST));
        $this->assertSame(self::MIDDLEWARE_NAME_TEST, $this->middleware->getMiddlewareName());
    }

    public function testGetClass(): void
    {
        $this->assertSame($this->customClass::class, $this->middleware->getClass());
    }

    public function testSetClass(): void
    {
        $newCustomClass = new class () {
        };

        $this->assertInstanceOf(Middleware::class, $this->middleware->setClass($newCustomClass::class));
        $this->assertSame($newCustomClass::class, $this->middleware->getClass());
    }

    public function testGetMethodClass(): void
    {
        $this->assertSame(self::EXAMPLE_METHOD, $this->middleware->getMethodClass());
    }

    public function testSetMethodClass(): void
    {
        $this->assertInstanceOf(Middleware::class, $this->middleware->setMethodClass(self::EXAMPLE_METHOD_TEST));
        $this->assertSame(self::EXAMPLE_METHOD_TEST, $this->middleware->getMethodClass());
    }

    public function testGetParams(): void
    {
        $this->assertSame(self::EXAMPLE_PARAMS, $this->middleware->getParams());
    }

    public function testSetParams(): void
    {
        $this->assertInstanceOf(Middleware::class, $this->middleware->setParams(self::EXAMPLE_PARAMS_TEST));
        $this->assertSame(self::EXAMPLE_PARAMS_TEST, $this->middleware->getParams());
    }
}
