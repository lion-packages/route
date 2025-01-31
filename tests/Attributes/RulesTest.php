<?php

declare(strict_types=1);

namespace Tests\Attributes;

use Lion\Route\Attributes\Rules;
use Lion\Test\Test;
use ReflectionException;
use ReflectionMethod;
use PHPUnit\Framework\Attributes\Test as Testing;

class RulesTest extends Test
{
    /**
     * @throws ReflectionException
     */
    #[Testing]
    public function rules(): void
    {
        $class = new class
        {
            #[Rules('rule1', 'rule2')]
            public function myMethod(): void
            {
            }
        };

        $reflectionMethod = new ReflectionMethod($class, 'myMethod');

        $attributes = $reflectionMethod->getAttributes(Rules::class);

        $this->assertCount(1, $attributes);

        /** @var Rules $rulesInstance */
        $rulesInstance = $attributes[0]->newInstance();

        $this->assertSame(['rule1', 'rule2'], $rulesInstance->getRules());
    }
}
