<?php

namespace Framework\Kernel\Container;

class BoundMethod
{
    public static function call($container, $callback, array $parameters = [], $defaultMethod = null): mixed
    {
        return $callback();
    }
}
