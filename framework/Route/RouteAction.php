<?php

namespace Framework\Kernel\Route;

use Framework\Kernel\Support\Str;

class RouteAction
{
    public static function containsSerializedClosure(array $action): bool
    {
        return is_string($action['uses']) && Str::startsWith($action['uses'], [
                'O:47:"Laravel\\SerializableClosure\\SerializableClosure',
                'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure',
            ]) !== false;
    }
}