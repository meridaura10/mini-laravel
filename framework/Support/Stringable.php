<?php

namespace Framework\Kernel\Support;

use Closure;
use mysql_xdevapi\SqlStatementResult;

class Stringable
{
    protected string $value = '';

    public function __construct($value = '')
    {
        $this->value = (string) $value;
    }

    public function trim(string $characters = null): static
    {
        return new static(trim(...array_merge([$this->value], func_get_args())));
    }

    public function when($value = null, callable $callback = null, callable $default = null): static|HigherOrderWhenProxy
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if (func_num_args() === 0) {
            return new HigherOrderWhenProxy($this);
        }

        if (func_num_args() === 1) {
            return (new HigherOrderWhenProxy($this))->condition($value);
        }

        if ($value) {
            return $callback($this, $value) ?? $this;
        } elseif ($default) {
            return $default($this, $value) ?? $this;
        }

        return $this;
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

    public function replaceFirst(string $search,string $replace): static
    {
        return new static(Str::replaceFirst($search, $replace, $this->value));
    }

    public function finish(string $cap): static
    {
        return new static(Str::finish($this->value, $cap));
    }

    public function __toString()
    {
        return (string) $this->value;
    }
}
