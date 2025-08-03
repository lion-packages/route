<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Closure;
use Lion\Dependency\Injection\Container;
use Lion\Route\Helpers\Rules;
use Lion\Test\Test;
use PHPUnit\Framework\Attributes\Test as Testing;
use ReflectionException;
use Valitron\Validator;

class RulesTest extends Test
{
    private Container $container;

    private object $rule;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $rule = new class () extends Rules {
            public function validate(Closure $validateFunction): void
            {
                parent::validate($validateFunction);
            }
        };

        $this->container = new Container();

        $this->rule = $this->container->resolve($rule::class);

        $this->initReflection($this->rule);
    }

    /**
     * @throws ReflectionException
     */
    #[Testing]
    public function validate(): void
    {
        $_POST['id'] = '';

        $this->container->callMethod($this->rule, 'validate', [
            'validateFunction' => function (Validator $validator): void {
                $validator
                    ->rule('required', 'id')
                    ->message('custom message');
            },
        ]);

        $errors = $this->getPrivateProperty('responses');

        $this->assertIsArray($errors);
        $this->assertArrayHasKey('id', $errors);
        $this->assertSame(['id' => ['custom message']], $errors);

        unset($_POST['id']);

        $this->assertArrayNotHasKey('id', $_POST);
    }

    #[Testing]
    public function getErrors(): void
    {
        $_POST['id'] = '';

        $this->rule->validate(function (Validator $validator): void {
            $validator
                ->rule('required', 'id')
                ->message('custom message');
        });

        $errors = $this->rule->getErrors();

        $this->assertIsArray($errors);
        $this->assertArrayHasKey('id', $errors);
        $this->assertSame(['id' => ['custom message']], $errors);

        unset($_POST['id']);

        $this->assertArrayNotHasKey('id', $_POST);
    }
}
