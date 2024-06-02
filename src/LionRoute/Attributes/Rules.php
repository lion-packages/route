<?php

declare(strict_types=1);

namespace Lion\Route\Attributes;

use Attribute;

/**
 * Attribute to reflect a rule on a method
 *
 * @package Lion\Route\Attributes
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Rules
{
    /**
     * [List of rules]
     *
     * @var array<int, string> $rules
     */
    private array $rules;

    /**
     * Class constructor
     *
     * @param string[] $rules [List of rules]
     */
    public function __construct(string ...$rules)
    {
        $this->rules = $rules;
    }

    /**
     * Returns a list with the namespaces of the rules
     *
     * @return array<int, string>
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
