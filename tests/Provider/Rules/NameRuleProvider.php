<?php

declare(strict_types=1);

namespace Tests\Provider\Rules;

use Lion\Route\Helpers\Rules;
use Lion\Route\Interface\RulesInterface;
use Valitron\Validator;

class NameRuleProvider extends Rules implements RulesInterface
{
    /**
     * [field for 'name'].
     *
     * @var string
     */
    public string $field = 'name';

    /**
     * [description for 'name'].
     *
     * @var string
     */
    public string $desc = 'name';

    /**
     * [value for 'name'].
     *
     * @var string;
     */
    public string $value = 'name';

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
                ->message("the 'name' property is required");
        });
    }
}
