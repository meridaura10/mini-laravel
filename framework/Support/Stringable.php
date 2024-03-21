<?php

namespace Framework\Kernel\Support;

class Stringable
{
    protected string $value = '';

    public function __construct($value = '')
    {
        $this->value = (string) $value;
    }

    public function endsWith(string|iterable $needles): bool
    {
        return Str::endsWith($this->value, $needles);
    }
}
