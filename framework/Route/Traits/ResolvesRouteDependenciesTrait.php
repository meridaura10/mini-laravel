<?php

namespace Framework\Kernel\Route\Traits;

use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Reflector;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use stdClass;
use function Symfony\Component\Translation\t;

trait ResolvesRouteDependenciesTrait
{
    protected function resolveClassMethodDependencies(array $parameters, mixed $instance, string $method): array
    {
        if (! method_exists($instance, $method)) {
            return $parameters;
        }

        return $this->resolveMethodDependencies(
            $parameters, new ReflectionMethod($instance, $method)
        );
    }

    public function resolveMethodDependencies(array $parameters, ReflectionFunctionAbstract $reflector): array
    {
        $instanceCount = 0;

        $value = array_values($parameters);

        $skippableValue = new stdClass();

        foreach ($reflector->getParameters() as $key => $parameter) {
            $instance = $this->transformDependency($parameter, $parameters, $skippableValue);

            if ($instance !== $skippableValue) {
                $instanceCount++;

                $this->spliceIntoParameters($parameters, $key, $instance);
            } elseif (! isset($values[$key - $instanceCount]) &&
                $parameter->isDefaultValueAvailable()) {
                $this->spliceIntoParameters($parameters, $key, $parameter->getDefaultValue());
            }
        }

        return $parameters;
    }

    protected function spliceIntoParameters(array &$parameters, $offset, $value): void
    {
        array_splice(
            $parameters, $offset, 0, [$value]
        );
    }

    protected function transformDependency(ReflectionParameter $parameter, array $parameters, stdClass $skippableValue): mixed
    {
        $className = Reflector::getParameterClassName($parameter);

        if($className && !$this->alreadyInParameters($className, $parameters)){
            $isEnum = (new ReflectionClass($className))->isEnum();

            return $parameter->isDefaultValueAvailable()
                ? ($isEnum ? $parameter->getDefaultValue() : null)
                : $this->app->make($className);
        }

        return $skippableValue;
    }

    protected function alreadyInParameters(string $class, array $parameters): bool
    {
        return ! is_null(Arr::first($parameters, fn ($value) => $value instanceof $class));
    }
}
