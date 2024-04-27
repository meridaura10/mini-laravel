<?php

namespace Framework\Kernel\Http\Requests\Traits;

use Framework\Kernel\Database\Eloquent\Model;

trait InteractsWithFlashDataTrait
{
    public function old(?string $key = null,mixed $default = null): mixed
    {
        $default = $default instanceof Model ? $default->getAttribute($key) : $default;

        return $this->hasSession() ? $this->session()->getOldInput($key, $default) : $default;
    }
}