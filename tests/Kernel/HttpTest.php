<?php

declare(strict_types=1);

namespace Tests\Kernel;

use DI\DependencyException;
use DI\NotFoundException;
use Lion\Dependency\Injection\Container;
use Lion\Exceptions\Exception;
use Lion\Request\Http;
use Lion\Request\Status;
use Lion\Route\Exceptions\RulesException;
use Lion\Route\Helpers\Rules;
use Lion\Route\Interface\RulesInterface;
use Lion\Route\Kernel\Http as KernelHttp;
use Lion\Test\Test;
use PHPUnit\Framework\Attributes\Test as Testing;
use Valitron\Validator;

class HttpTest extends Test
{
    private const string MESSAGE = 'parameter error';

    private KernelHttp $kernelHttp;
    private Container $container;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function setUp(): void
    {
        $this->container = new Container();

        /** @var KernelHttp $kernelHttp */
        $kernelHttp = $this->container->resolve(KernelHttp::class);

        $this->kernelHttp = $kernelHttp;
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);

        unset($_SERVER['REQUEST_METHOD']);

        $this->rmdirRecursively('./app/');
    }

    /**
     * @throws Exception
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[Testing]
    public function validateRules(): void
    {
        $rule = new class () extends Rules implements RulesInterface {
            /**
             * {@inheritdoc}
             */
            public function passes(): void
            {
                $this->validate(function (Validator $validator) {
                    $validator
                        ->rule('required', 'id')
                        ->message("the 'id' property is optional");
                });
            }
        };

        $rule2 = new class () extends Rules implements RulesInterface {
            /**
             * {@inheritdoc}
             */
            public function passes(): void
            {
                $this->validate(function (Validator $validator) {
                    $validator
                        ->rule('optional', 'name')
                        ->message("the 'name' property is optional");
                });
            }
        };

        /** @var Rules|RulesInterface $idRule */
        $idRule = $this->container->resolve($rule::class);

        /** @var Rules|RulesInterface $nameRule */
        $nameRule = $this->container->resolve($rule2::class);

        $this
            ->exception(RulesException::class)
            ->exceptionMessage(self::MESSAGE)
            ->exceptionStatus(Status::RULE_ERROR)
            ->exceptionCode(Http::INTERNAL_SERVER_ERROR)
            ->expectLionException(function () use ($idRule, $nameRule): void {
                $this->kernelHttp->validateRules([$idRule::class, $nameRule::class]);
            });
    }
}
