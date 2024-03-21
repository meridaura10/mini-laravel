<?php

namespace Framework\Kernel\Facades\Services;

use Framework\Kernel\Facades\Facade;

/**
 * @method static \Framework\Kernel\Route\Route get(string $uri, array|string $action): void
 * @method static \Framework\Kernel\Route\Route post(string $uri, array|string $action): void
 * @method static \Framework\Kernel\Route\Contracts\RouteGroupInterface prefix(string $uri)
 * @method static \Framework\Kernel\Route\Contracts\RouteGroupInterface controller(string $controller)
 * @method static \Framework\Kernel\Route\Contracts\RouteGroupInterface name(string $name)
 * @method static \Framework\Kernel\Route\Contracts\RouteGroupInterface middleware(string|array $middlewares)
 */
class Route extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'router';
    }
}
