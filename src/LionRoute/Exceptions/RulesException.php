<?php

declare(strict_types=1);

namespace Lion\Route\Exceptions;

use Lion\Exceptions\Exception;
use Lion\Exceptions\Interfaces\ExceptionInterface;
use Lion\Exceptions\Traits\ExceptionTrait;

/**
 * Exceptions to the rules
 *
 * @package Lion\Route\Exceptions
 */
class RulesException extends Exception implements ExceptionInterface
{
    use ExceptionTrait;
}
