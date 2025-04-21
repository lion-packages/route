<?php

declare(strict_types=1);

namespace Lion\Route\Interface;

/**
 * Defines the configuration for validation of the defined rules.
 */
interface RulesInterface
{
    /**
     * Run the rule definition.
     *
     * @return void
     */
    public function passes(): void;
}
