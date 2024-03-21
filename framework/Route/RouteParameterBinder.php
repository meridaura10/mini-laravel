<?php

namespace Framework\Kernel\Route;

use Framework\Kernel\Http\Requests\Contracts\RequestInterface;

class RouteParameterBinder
{
    public function __construct(protected Route $route)
    {

    }

    public function parameters(RequestInterface $request): array
    {
        return $this->bindPathParameters($request);
    }

    protected function bindPathParameters(RequestInterface $request): array
    {
        preg_match($this->route->getCompiled()->getRegex(), $request->uri(), $matches);

        return $this->matchToKeys(array_slice($matches, 1));
    }

    protected function matchToKeys(array $matches): array
    {
        return array_intersect_key($matches, array_flip($this->route->getCompiled()->getVariables()));
    }
}
