<?php

namespace Framework\Kernel\Http\Requests\Traits;

trait CanBePrecognitiveTrait
{
    public function isPrecognitive(): bool
    {
        return $this->attributes->get('precognitive', false);
    }
}