<?php

namespace Framework\Kernel\Route;

use App\Models\Product;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Database\Eloquent\Trait\SoftDeletesTrait;
use Framework\Kernel\Database\Exceptions\ModelNotFoundException;
use Framework\Kernel\Route\Contracts\UrlRoutableInterface;
use Framework\Kernel\Route\Exceptions\BackedEnumCaseNotFoundException;
use Framework\Kernel\Support\Reflector;
use Framework\Kernel\Support\Str;

class ImplicitRouteBinding
{
    public static function resolveForRoute(ApplicationInterface $container, Route $route): void
    {
        $parameters = $route->parameters();

        $route = static::resolveBackedEnumsForRoute($route, $parameters);

        foreach ($route->signatureParameters(['subClass' => UrlRoutableInterface::class]) as $parameter){
            if (! $parameterName = static::getParameterName($parameter->getName(), $parameters)) {
                continue;
            }

            $parameterValue = $parameters[$parameterName];

            if($parameterValue instanceof UrlRoutableInterface){
                continue;
            }

            $instance = $container->make(Reflector::getParameterClassName($parameter));

            $parent = $route->parentOfParameter($parameterName);

            $routeBindingMethod = $route->allowsTrashedBindings() && in_array(SoftDeletesTrait::class, class_uses_recursive($instance))
                ? 'resolveSoftDeletableRouteBinding'
                : 'resolveRouteBinding';


            if(! $model = $instance->{$routeBindingMethod}($parameterValue, $route->bindingFieldFor($parameterName))){
                throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
            }

            $route->setParameter($parameterName, $model);
        }
    }

    protected static function resolveBackedEnumsForRoute(Route $route, array $parameters): Route
    {
        foreach ($route->signatureParameters(['backedEnum' => true]) as $parameter){
            if(! $parameterName = static::getParameterName($parameter->getName(), $parameters)){
                continue;
            };

            $parameterValue = $parameters[$parameterName];

            $backedEnumClass = $parameter->getType()?->getName();

            $backedEnum = $backedEnumClass::tryFrom((string) $parameterValue);

            if(is_null($backedEnum)){
                throw new BackedEnumCaseNotFoundException($backedEnumClass, $parameterValue);
            }

            $route->setParameter($parameterName, $backedEnum);
        }

        return $route;
    }

    protected static function getParameterName(string $name, array $parameters): ?string
    {
        if (array_key_exists($name, $parameters)) {
            return $name;
        }

        if (array_key_exists($snakedName = Str::snake($name), $parameters)) {
            return $snakedName;
        }

        return null;
    }
}