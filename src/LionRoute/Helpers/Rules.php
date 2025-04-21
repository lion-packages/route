<?php

declare(strict_types=1);

namespace Lion\Route\Helpers;

use Closure;
use DI\Attribute\Inject;
use Lion\Request\Request;
use Lion\Security\Validation;

/**
 * Define the rules and execute their validations.
 *
 * @property Validation                $validation [Validation class object]
 * @property Request                   $request    [Allows you to obtain data captured in an HTTP
 *                                                 request and modify headers]
 * @property array<int|string, string> $responses  [Array containing all answers]
 */
class Rules
{
    /**
     * [Validation class object].
     *
     * @var Validation
     */
    private Validation $validation;

    /**
     * Allows you to obtain data captured in an HTTP request and modify headers.
     *
     * @var Request
     */
    private Request $request;

    /**
     * [Array containing all answers].
     *
     * @var array<int|string, string>
     */
    protected array $responses = [];

    #[Inject]
    public function setValidation(Validation $validation): void
    {
        $this->validation = $validation;
    }

    #[Inject]
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Executes the validation of the Validate object of Validator.
     *
     * @param Closure $validateFunction [Function that executes the rules
     *                                  defined in the Validator object]
     *
     * @return void
     */
    protected function validate(Closure $validateFunction): void
    {
        /** @var array<string, mixed> $rows */
        $rows = (array) $this->request->capture();

        $response = $this->validation->validate($rows, $validateFunction);

        if ('error' === $response->status) {
            /** @var array<int|string, string> $errorMessages */
            $errorMessages = $response->messages;

            $this->responses = $errorMessages;
        }
    }

    /**
     * Gets the list of rule errors.
     *
     * @return array<int|string, string>
     */
    public function getErrors(): array
    {
        return $this->responses;
    }
}
