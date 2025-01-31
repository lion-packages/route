<?php

declare(strict_types=1);

namespace Tests\Helpers;

use DI\DependencyException;
use DI\NotFoundException;
use Lion\Dependency\Injection\Container;
use Lion\Route\Helpers\Rules;
use Lion\Test\Test;
use PHPUnit\Framework\Attributes\Test as Testing;
use ReflectionException;
use Valitron\Validator;

class RulesTest extends Test
{
    private Container $container;
    private Rules $rules;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->container = new Container();

        /** @var Rules $rule */
        $rule = $this->container->resolve(Rules::class);

        $this->rules = $rule;

        $this->initReflection($this->rules);
    }

    /**
     * @throws ReflectionException
     */
    #[Testing]
    public function validate(): void
    {
        $_POST['id'] = '';

        $this->getPrivateMethod('validate', [
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

    /**
     * @throws ReflectionException
     */
    #[Testing]
    public function getErrors(): void
    {
        $_POST['id'] = '';

        $this->getPrivateMethod('validate', [
            'validateFunction' => function (Validator $validator): void {
                $validator
                    ->rule('required', 'id')
                    ->message('custom message');
            },
        ]);

        $errors = $this->rules->getErrors();

        $this->assertArrayHasKey('id', $errors);
        $this->assertSame(['id' => ['custom message']], $errors);

        unset($_POST['id']);

        $this->assertArrayNotHasKey('id', $_POST);
    }
}
