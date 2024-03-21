<?php

namespace Framework\Kernel\Route;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Route\Traits\ResolvesRouteDependenciesTrait;

class ControllerDispatcher
{
    use ResolvesRouteDependenciesTrait;

    public function __construct(
        protected ApplicationInterface $app,
    ) {

    }

    public function dispatch(Route $route, mixed $controller, ?string $method): mixed
    {
        $parameters = $this->resolveParameters($route, $controller, $method);

        return $controller->{$method}(...array_values($parameters));
    }

    protected function resolveParameters(Route $route, $controller, $method): array
    {
        return $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $controller, $method,
        );
    }
}
