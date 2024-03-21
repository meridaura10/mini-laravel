<?php

namespace Framework\Kernel\Facades\Services;

use Framework\Kernel\Facades\Facade;

/**
 * @method static \Framework\Kernel\View\Contracts\ViewInterface make(string $view, \Framework\Kernel\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 */
class View extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'view';
    }
}
