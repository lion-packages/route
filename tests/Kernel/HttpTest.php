<?php

declare(strict_types=1);

namespace Tests\Kernel;

use Lion\Dependency\Injection\Container;
use Lion\Request\Http;
use Lion\Request\Status;
use Lion\Route\Exceptions\RulesException;
use Lion\Route\Helpers\Rules;
use Lion\Route\Interface\RulesInterface;
use Lion\Route\Kernel\Http as KernelHttp;
use Lion\Test\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Provider\HttpProviderTrait;
use Valitron\Validator;

class HttpTest extends Test
{
    use HttpProviderTrait;

    const MESSAGE = 'parameter error';
    const URI = '/api/test';

    private KernelHttp $kernelHttp;

    protected function setUp(): void
    {
        $this->kernelHttp = (new Container())->injectDependencies(new KernelHttp());
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);

        unset($_SERVER['REQUEST_METHOD']);

        $this->rmdirRecursively('./app/');
    }

    #[DataProvider('checkUrlProvider')]
    public function testCheckUrl(string $requestUri, string $uri, bool $response): void
    {
        $_SERVER['REQUEST_URI'] = $requestUri;

        $this->assertSame($response, $this->kernelHttp->checkUrl($uri));
    }

    public function testValidateRules(): void
    {
        $idRule = (new Container())->injectDependencies(new class extends Rules implements RulesInterface
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
        });

        $nameRule = (new Container())->injectDependencies(new class extends Rules implements RulesInterface
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
        });

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
