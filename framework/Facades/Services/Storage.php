<?php

namespace Framework\Kernel\Facades\Services;

use Framework\Kernel\Facades\Facade;

class Storage extends Facade
{

    protected static function getFacadeAccessor(): string
    {
       return 'filesystem';
    }
}