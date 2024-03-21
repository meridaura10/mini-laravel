<?php

namespace Framework\Kernel\Route\Traits;

use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use stdClass;

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
        if (! $parameter->getType()) {
            return $skippableValue;
        }

        if ($parameter->getType() instanceof \ReflectionNamedType) {
            $className = $parameter->getType()->getName();

            return $this->app->make($className);
        }

        return $skippableValue;
    }
}
