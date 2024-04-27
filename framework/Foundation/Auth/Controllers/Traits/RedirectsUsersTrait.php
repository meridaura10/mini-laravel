<?php

namespace Framework\Kernel\Foundation\Auth\Controllers\Traits;

trait RedirectsUsersTrait
{
    public function redirectPath(): string
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/';
    }
}