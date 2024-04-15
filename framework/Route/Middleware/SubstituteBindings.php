<?php

namespace Framework\Kernel\Route\Middleware;

use Closure;
use Framework\Kernel\Database\Exceptions\ModelNotFoundException;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Route\Contracts\RouteRegistrarInterface;

class SubstituteBindings
{
    public function __construct(
        protected RouteRegistrarInterface $router,
    ) {

    }
    public function handle(RequestInterface $request, Closure $next): mixed
    {
        try {
            $this->router->substituteBindings($route = $request->route());

            $this->router->substituteImplicitBindings($route);
        } catch (ModelNotFoundException $exception) {
            if ($route->getMissing()) {
                return $route->getMissing()($request, $exception);
            }

            throw $exception;
        }

        return $next($request);
    }
}