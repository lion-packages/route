<?php

declare(strict_types=1);

namespace Tests\Provider\Rules;

use Lion\Route\Helpers\Rules;
use Lion\Route\Interface\RulesInterface;
use Valitron\Validator;

class IdRuleProvider extends Rules implements RulesInterface
{
    /**
     * [field for 'id'].
     *
     * @var string
     */
    public string $field = 'id';

    /**
     * [description for 'id'].
     *
     * @var string
     */
    public string $desc = 'id';

    /**
     * [value for 'id'].
     *
     * @var string;
     */
    public string $value = 'id';

    /**
     * [Defines whether the column is optional for postman collections].
     *
     * @var bool;
     */
    public bool $disabled = false;

    /**
     * {@inheritdoc}
     */
    public function passes(): void
    {
        $this->validate(function (Validator $validator) {
            $validator
                ->rule('required', $this->field)
                ->message("the 'id' property is required");
        });
    }
}
