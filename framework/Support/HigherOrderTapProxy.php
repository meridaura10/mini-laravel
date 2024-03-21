<?php

namespace Framework\Kernel\Support;

class HigherOrderTapProxy
{
    public function __construct(public readonly mixed $target)
    {

    }

    public function __call($method, $parameters)
    {
        $this->target->{$method}(...$parameters);

        return $this->target;
    }
}
