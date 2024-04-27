<?php

namespace Framework\Kernel\Facades\Services;

use Framework\Kernel\Facades\Facade;

class Auth extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return 'auth';
    }

    public static function routes(array $options = []): void
    {
        static::$app->make('router')->auth($options);
    }
}