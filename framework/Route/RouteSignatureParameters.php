<?php

namespace Framework\Kernel\Route;

use Framework\Kernel\Support\Reflector;
use ReflectionEnum;
use ReflectionMethod;
use ReflectionNamedType;

class RouteSignatureParameters
{
    public static function fromAction(array $action, array $conditions = []): array
    {
        $parameters = static::fromClassMethodString($action['controller'], $action['uses']);

        return match (true) {
            ! empty($conditions['subClass']) => array_filter($parameters, fn ($p) => Reflector::isParameterSubclassOf($p, $conditions['subClass'])),
            ! empty($conditions['backedEnum']) => array_filter($parameters, fn ($p) => Reflector::isParameterBackedEnumWithStringBackingType($p)),
            default => $parameters,
        };
    }

    protected static function fromClassMethodString(string $class, string $method): array
    {
        return (new ReflectionMethod($class, $method))->getParameters();
    }
}