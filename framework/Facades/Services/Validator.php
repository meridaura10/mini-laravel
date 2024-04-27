<?php

namespace Framework\Kernel\Facades\Services;

use Framework\Kernel\Facades\Facade;

class Validator extends Facade
{

    protected static function getFacadeAccessor(): string
    {
       return 'validator';
    }
}