<?php

namespace Framework\Kernel\Route;

use Framework\Kernel\Http\Exception\MethodNotAllowedHttpException;
use Framework\Kernel\Http\Exception\NotFoundHttpException;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;

class RouteCollection
{
    protected array $routes = [];

    protected array $allRoutes = [];

    protected array $nameList = [];

    protected array $actionList = [];

    public function add(Route $route): Route
    {
        $this->addToCollections($route);

        //        $this->addLookups($route);

        return $route;
    }

    protected function addToCollections(Route $route): void
    {
        $domainAndUri = $route->getDomain().$route->uri();

        $this->routes[$route->method()][$domainAndUri] = $route;

        $this->allRoutes[$route->method().$domainAndUri] = $route;
    }

    public function match(RequestInterface $request): ?Route
    {
        $routes = $this->get($request->method());

        $route = $this->matchAgainstRoutes($routes, $request);

        return $this->handleMatchedRoute($request, $route);
    }

    public function get(?string $method = null): array
    {
        return $method ? $this->routes[$method] ?? [] : $this->getRoutes();
    }

    public function getRoutes(): array
    {
        return array_values($this->allRoutes);
    }

    protected function matchAgainstRoutes(array $routes, RequestInterface $request): ?Route
    {
        foreach ($routes as $route) {
            if ($route->matches($request)) {
                return $route;
            }
        }

        return null;
    }

    protected function handleMatchedRoute(RequestInterface $request, ?Route $route): ?Route
    {
        if (! is_null($route)) {
            return $route->bind($request);
        }

        $others = $this->checkForAlternateVerbs($request);

        if (count($others) > 0) {
            $this->requestMethodNotAllowed($others, $request);
        }

        throw new NotFoundHttpException(sprintf(
            'The route %s could not be found.',
            $request->uri()
        ));
    }

    protected function requestMethodNotAllowed(array $others, RequestInterface $request): void
    {
        throw new MethodNotAllowedHttpException(
            sprintf(
                'The %s method is not supported for route %s. Supported methods: %s.',
                $request->method(),
                $request->uri(),
                implode(', ', $others)
            ),
        );
    }

    protected function checkForAlternateVerbs(RequestInterface $request): array
    {
        $methods = array_diff(Router::$verbs, [$request->method()]);

        return array_values(array_filter(
            $methods,
            function ($method) use ($request) {
                return $this->matchAgainstRoutes($this->get($method), $request);
            }
        ));

    }
}
