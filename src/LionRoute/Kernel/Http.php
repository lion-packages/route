<?php

declare(strict_types=1);

namespace Lion\Route\Kernel;

use DI\DependencyException;
use DI\NotFoundException;
use Lion\Dependency\Injection\Container;
use Lion\Request\Http as RequestHttp;
use Lion\Request\Status;
use Lion\Route\Exceptions\RulesException;
use Lion\Route\Helpers\Rules;
use Lion\Route\Interface\RulesInterface;

/**
 * Kernel for HTTP requests
 *
 * @property Container $container [Container to generate dependency injection]
 *
 * @package Lion\Route\Kernel
 */
class Http
{
    /**
     * Class constructor
     *
     * @param Container $container [Container to generate dependency injection]
     */
    public function __construct(
        private readonly Container $container
    ) {
    }

    /**
     * Check URL patterns to validate if a URL matches or is identical
     *
     * @param string $uri [API URI]
     *
     * @return bool
     */
    public function checkUrl(string $uri): bool
    {
        /** @var string $requestUri */
        $requestUri = $_SERVER['REQUEST_URI'];

        $cleanRequestUri = explode('?', $requestUri)[0];

        $arrayUri = explode('/', $uri);

        $arrayUrl = explode('/', $cleanRequestUri);

        foreach ($arrayUri as $index => &$value) {
            if (preg_match('/^\{.*\}$/', $value)) {
                $value = 'dynamic-param';

                $arrayUrl[$index] = 'dynamic-param';
            }
        }

        return implode('/', $arrayUri) === implode('/', $arrayUrl);
    }

    /**
     * Check for errors with the defined rules
     *
     * @param array<int, string> $rules [List of rules]
     *
     * @return void
     *
     * @throws DependencyException [Error while resolving the entry]
     * @throws NotFoundException [No entry found for the given name]
     * @throws RulesException [If there are rule errors]
     */
    public function validateRules(array $rules): void
    {
        $errors = [];

        foreach ($rules as $rule) {
            /** @var RulesInterface|Rules $ruleClass */
            $ruleClass = $this->container->resolve($rule);

            if ($ruleClass instanceof RulesInterface) {
                $ruleClass->passes();

                if ($ruleClass instanceof Rules) {
                    $ruleKey = array_keys($ruleClass->getErrors());

                    $ruleErrors = array_values($ruleClass->getErrors());

                    if (!empty($ruleErrors)) {
                        $key = reset($ruleKey);

                        $error = reset($ruleErrors);

                        $errors[$key] = $error;
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw new RulesException('parameter error', Status::RULE_ERROR, RequestHttp::INTERNAL_SERVER_ERROR, [
                'rules-error' => $errors,
            ]);
        }
    }
}
