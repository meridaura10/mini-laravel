<?php

namespace Framework\Kernel\Support;

use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;

class Reflector
{
    public static function getParameterClassName(\ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if(! $type instanceof \ReflectionNamedType || $type->isBuiltin()){
            return null;
        }

        return static::getTypeName($parameter,$type);
    }

    public static function isParameterBackedEnumWithStringBackingType(\ReflectionParameter $parameter): bool
    {
        if (! $parameter->getType() instanceof ReflectionNamedType) {
            return false;
        }

        $backedEnumClass = $parameter->getType()?->getName();

        if (is_null($backedEnumClass)) {
            return false;
        }

        if (enum_exists($backedEnumClass)) {
            $reflectionBackedEnum = new ReflectionEnum($backedEnumClass);

            return $reflectionBackedEnum->isBacked()
                && $reflectionBackedEnum->getBackingType()->getName() == 'string';
        }

        return false;
    }

    public static function isParameterSubclassOf(\ReflectionParameter|string $parameter,string $className): bool
    {
        $paramClassName = static::getParameterClassName($parameter);

        return $paramClassName
            && (class_exists($paramClassName) || interface_exists($paramClassName))
            && (new ReflectionClass($paramClassName))->isSubclassOf($className);
    }

    protected static function getTypeName(\ReflectionParameter $parameter,\ReflectionNamedType $type): string
    {
        $name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }
}