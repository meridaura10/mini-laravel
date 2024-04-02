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

    public function beforeLast(string $search): static
    {
        return new static(Str::beforeLast($this->value, $search));
    }

    public function plural(int $count = 2): static
    {
        return new static(Str::plural($this->value, $count));
    }

    public function __toString()
    {
        return (string) $this->value;
    }
}
