<?php

declare(strict_types=1);

namespace Tests\Attributes;

use Lion\Route\Attributes\Rules;
use Lion\Test\Test;
use ReflectionMethod;

class RulesTest extends Test
{
    public function testRules(): void
    {
        $class = new class
        {
            /**
             * @Rules('rule1', 'rule2')
             */
            #[Rules('rule1', 'rule2')]
            public function myMethod()
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
