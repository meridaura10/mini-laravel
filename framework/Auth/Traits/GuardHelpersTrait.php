<?php

namespace Framework\Kernel\Auth\Traits;

trait GuardHelpersTrait
{
    public function check(): bool
    {
        return ! is_null($this->user());
    }

    public function guest(): bool
    {
        return ! $this->check();
    }
}