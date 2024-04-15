<?php

namespace Framework\Kernel\Route;

use ArrayObject;
use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Contracts\Support\Jsonable;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;
use Framework\Kernel\Http\Responses\JsonResponse;
use Framework\Kernel\Http\Responses\Response;
use Framework\Kernel\Pipeline\Pipeline;
use Framework\Kernel\Route\Contracts\RouteRegistrarInterface;
use Framework\Kernel\Route\Contracts\RouterInterface;
use JsonSerializable;
use stdClass;
use Stringable;

class Router implements RouterInterface, RouteRegistrarInterface
{
    public static array $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    protected array $groupStack = [];

    protected RouteCollection $routes;

    protected array $middlewarePriority = [];

    protected array $middlewareGroups = [];

    protected array $middleware = [];

    protected RequestInterface $currentRequest;

    protected ?Closure $implicitBindingCallback = null;

    protected ?Route $current = null;

    public function __construct(
        protected ApplicationInterface $app,
    ) {
        $this->routes = new RouteCollection();
    }

    public function dispatch(RequestInterface $request): ResponseInterface
    {
        $this->currentRequest = $request;

        return $this->dispatchToRoute($request);
    }

    protected function dispatchToRoute(RequestInterface $request): ResponseInterface
    {
        return $this->runRoute($request, $this->findRoute($request));
    }

    protected function runRoute(RequestInterface $request, Route $route): ResponseInterface
    {
        $request->setRouteResolver(fn () => $route);

        return $this->prepareResponse($request, $this->runRouteWithinStack($route, $request));
    }

    protected function runRouteWithinStack(Route $route, RequestInterface $request): mixed
    {
        $middleware = $this->gatherRouteMiddleware($route);

        return (new Pipeline($this->app))
            ->send($request)
            ->through($middleware)
            ->then(function (RequestInterface $request) use ($route) {
                return $route->run();
            });
    }

    protected function prepareResponse(RequestInterface $request, mixed $response): ResponseInterface
    {
        return static::toResponse($request, $response);
    }

    public static function toResponse(RequestInterface $request, mixed $response): ResponseInterface
    {
        if ($response instanceof ResponseInterface) {
            return $response->prepare($request);
        }

        $response = match (true) {
            $response instanceof Arrayable,
            $response instanceof Jsonable,
            $response instanceof ArrayObject,
            $response instanceof JsonSerializable,
            $response instanceof stdClass,
            is_array($response) => new JsonResponse($response),
            $response instanceof Stringable => new Response($response->__toString(), 200, ['Content-Type' => 'text/html']),
            default => new Response($response, 200, ['Content-Type' => 'text/html']),
        };

        return $response->prepare($request);
    }

    protected function gatherRouteMiddleware(Route $route): array
    {
        return $this->resolveMiddleware($route->gatherMiddleware());
    }

    protected function resolveMiddleware(array $middleware): array
    {
        $result = [];

        foreach ($middleware as $item) {
            if (isset($this->middlewareGroups[$item])) {
                $result = array_merge($result, $this->middlewareGroups[$item]);

                continue;
            }

            if ($this->middleware[$item]) {
                $result[] = $this->middleware[$item];

                continue;
            }

            $result[] = $item;
        }

        return $result;
    }

    public function findRoute(RequestInterface $request): ?Route
    {
        $route = $this->routes->match($request);

        if (! $route) {
            return null;
        }

        $route->setContainer($this->app);

        return $this->current = $route;
    }

    public function get(string $uri, array|string $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, array|string $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function group(array $attributes, string|callable $routes): void
    {
        $this->updateGroupStack($attributes);

        $this->loadRoutes($routes);

        array_pop($this->groupStack);
    }

    protected function loadRoutes(string|callable $routes): void
    {
        if ($routes instanceof Closure) {
            $routes($this);
        } else {
            include $routes;
        }
    }

    public function addRoute(string $method, string $uri, array|string $action): Route
    {
        return $this->routes->add($this->createRoute($method, $uri, $action));
    }

    protected function createRoute(string $method, string $uri, array|string $action): Route
    {
        $route = $this->newRoute(
            $method,
            $this->prefix($uri),
            $this->action($action),
            $this->name(),
        );

        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($route);
        }

        return $route;
    }

    protected function mergeGroupAttributesIntoRoute(Route $route): void
    {
        $last = end($this->groupStack);

        $route->middleware($last['middleware'] ?? []);
    }

    public function name(): ?string
    {
        if ($this->hasGroupStack()) {
            return end($this->groupStack)['name'] ?? null;
        }

        return null;
    }

    public function hasGroupStack(): bool
    {
        return ! empty($this->groupStack);
    }

    protected function updateGroupStack(array $attributes): void
    {
        if ($this->hasGroupStack()) {
            $attributes = $this->mergeWithLastGroup($attributes);
        }

        $this->groupStack[] = $attributes;
    }

    public function mergeWithLastGroup(array $attributes): array
    {
        return RouteGroup::merge($attributes, end($this->groupStack));
    }

    protected function action(array|string $action): array
    {
        if (is_array($action)) {
            return [
                'controller' => $action[0],
                'uses' => $action[1],
            ];
        }

        if (class_exists($action)) {
            return [
                'uses' => '__invoke',
                'controller' => $action,
            ];
        }

        if (is_string($action)) {
            return [
                'uses' => $action,
                'controller' => $this->getLastGroupController(),
            ];
        }

        return $action;
    }

    protected function newRoute(string $method, string $uri, array $action, ?string $name): Route
    {
        return (new Route($method, $uri, $action, $name))->setRouter($this);
    }

    protected function prefix(string $uri): string
    {
        return trim(trim($this->getLastGroupPrefix(), '/').'/'.trim($uri, '/'), '/') ?: '/';
    }

    private function getLastGroupPrefix(): string
    {
        if ($this->hasGroupStack()) {
            $last = end($this->groupStack);

            return $last['prefix'] ?? '';
        }

        return '';
    }

    private function getLastGroupController(): ?string
    {
        if ($this->hasGroupStack()) {
            $last = end($this->groupStack);

            return $last['controller'] ?? null;
        }

        return null;
    }

    public function setMiddlewarePriority(array $middleware): void
    {
        $this->middlewarePriority = $middleware;
    }

    public function setMiddlewareGroup(string $name, array $middleware): void
    {
        $this->middlewareGroups[$name] = $middleware;
    }

    public function setAliasMiddleware(string $name, string $middleware): void
    {
        $this->middleware[$name] = $middleware;
    }

    public function __call($method, $parameters): RouteRegistrar
    {
        if ($method === 'middleware') {
            return (new RouteRegistrar($this))->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
        }

        return (new RouteRegistrar($this))->attribute($method, array_key_exists(0, $parameters) ? $parameters[0] : true);
    }

    public function substituteBindings(Route $route): Route
    {
        foreach ($route->parameters() as $key => $parameter){
            if(isset($this->binders[$key])){

            }
        }

        return $route;
    }

    public function substituteImplicitBindings(Route $route): mixed
    {
       $default = fn() => ImplicitRouteBinding::resolveForRoute($this->app,$route);

        return call_user_func(
            $this->implicitBindingCallback ?? $default, $this->app, $route, $default
        );
    }

    public function getImplicitBindingCallback(): ?Closure
    {
        return $this->implicitBindingCallback;
    }

    public function setImplicitBindingCallback(?Closure $implicitBindingCallback): void
    {
        $this->implicitBindingCallback = $implicitBindingCallback;
    }
}
