<?php

namespace Framework\Kernel\Support;

class HigherOrderWhenProxy
{
    protected bool $condition;

    protected bool $hasCondition = false;

    public function __construct(
        protected mixed $target,
    )
    {

    }

    public function condition(bool $condition): static
    {
        [$this->condition, $this->hasCondition] = [$condition, true];

        return $this;
    }
}