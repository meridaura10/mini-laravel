<?php

namespace Framework\Kernel\Facades;

use Framework\Kernel\Application\Contracts\ApplicationInterface;

abstract class Facade
{
    protected static array $resolvedInstance = [];

    protected static bool $cached = true;

    protected static ?ApplicationInterface $app = null;

    abstract protected static function getFacadeAccessor(): string;

    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();

        return $instance->$method(...$args);
    }

    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    protected static function resolveFacadeInstance($name): mixed
    {
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        if (static::$app) {
            if (static::$cached) {
                return static::$resolvedInstance[$name] = static::$app->make($name);
            }

            return static::$app->make($name);
        }
    }

    public static function setFacadeApplication(ApplicationInterface $application): void
    {
        static::$app = $application;
    }

    public static function clearResolvedInstance(string $name): void
    {
        unset(static::$resolvedInstance[$name]);
    }
}
