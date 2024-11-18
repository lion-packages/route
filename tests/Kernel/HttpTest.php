<?php

declare(strict_types=1);

namespace Tests\Kernel;

use Lion\Dependency\Injection\Container;
use Lion\Exceptions\Exception;
use Lion\Request\Http;
use Lion\Request\Status;
use Lion\Route\Exceptions\RulesException;
use Lion\Route\Helpers\Rules;
use Lion\Route\Interface\RulesInterface;
use Lion\Route\Kernel\Http as KernelHttp;
use Lion\Test\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test as Testing;
use Tests\Provider\HttpProviderTrait;
use Valitron\Validator;

class HttpTest extends Test
{
    use HttpProviderTrait;

    private const string MESSAGE = 'parameter error';
    private const string URI = '/api/test';

    private KernelHttp $kernelHttp;
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();

        $this->kernelHttp = $this->container->resolve(KernelHttp::class);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);

        unset($_SERVER['REQUEST_METHOD']);

        $this->rmdirRecursively('./app/');
    }

    #[Testing]
    #[DataProvider('checkUrlProvider')]
    public function checkUrl(string $requestUri, string $uri, bool $response): void
    {
        $_SERVER['REQUEST_URI'] = $requestUri;

        $this->assertSame($response, $this->kernelHttp->checkUrl($uri));
    }

    /**
     * @throws Exception
     */
    #[Testing]
    public function validateRules(): void
    {
        $rule = new class extends Rules implements RulesInterface
        {
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

        $rule2 = new class extends Rules implements RulesInterface
        {
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

        $idRule = $this->container->resolve($rule::class);

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
