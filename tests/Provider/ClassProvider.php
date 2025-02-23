<?php

declare(strict_types=1);

namespace Tests\Provider;

class ClassProvider
{
    private string $index = 'index';

    public function getIndex(): string
    {
        return $this->index;
    }

    public function setIndex(string $index): ClassProvider
    {
        $this->index = $index;

        return $this;
    }
}
