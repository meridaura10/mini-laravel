<?php

namespace Framework\Kernel\Route;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Route\Contracts\RouterInterface;
use Framework\Kernel\Support\Str;

class Route
{
    protected RouterInterface $router;

    protected ?ApplicationInterface $container = null;

    private mixed $controller = null;

    private array $parameters = [];

    protected array $bindingFields = [];

    private ?RouteCompiled $compiled = null;

    protected bool $withTrashedBindings = false;

    public function __construct(
        private string $method,
        private string $uri,
        private array $action,
        private ?string $name = null,
        private array $middleware = [],
    ) {
    }

    public function name(string $name): static
    {
        $this->name = isset($this->name) ? $this->name.$name : $name;

        return $this;
    }

    public function middleware(array $middleware): static
    {
        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    public function gatherMiddleware(): array
    {
        return $this->middleware;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function getDomain(): ?string
    {
        return isset($this->action['domain'])
            ? str_replace(['http://', 'https://'], '', $this->action['domain']) : null;
    }

    public function setRouter(RouterInterface $router): static
    {
        $this->router = $router;

        return $this;
    }

    public function run(): mixed
    {
        return $this->controllerDispatcher()->dispatch($this, $this->getController(), $this->action['uses']);
    }

    public function parametersWithoutNulls(): array
    {
        return array_filter($this->parameters(), fn ($p) => ! is_null($p));
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    public function setParameter(string $name,mixed $value): void
    {
        $this->parameters[$name] = $value;
    }

    protected function getController(): mixed
    {
        if (! $this->controller) {
            $class = $this->action['controller'];

            $this->controller = $this->container->make($class);
        }

        return $this->controller;
    }

    public function controllerDispatcher(): ControllerDispatcher
    {
        return new ControllerDispatcher($this->container);
    }

    public function getMissing()
    {
        $missing = $this->action['missing'] ?? null;

        return is_string($missing) &&
        Str::startsWith($missing, [
            'O:47:"Laravel\\SerializableClosure\\SerializableClosure',
            'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure',
        ]) ? unserialize($missing) : $missing;
    }

    public function matches(RequestInterface $request): bool
    {
        $this->compiled();

        $path = rtrim($request->uri(), '/') ?: '/';

        if (preg_match($this->getCompiled()->getRegex(), $path)) {
            return true;
        }

        return false;
    }

    protected function compiled(): void
    {
        if (! $this->compiled) {
            $this->compiled = new RouteCompiled($this->uri());
        }
    }

    public function bindingFieldFor(int|string $parameter): ?string
    {
        $fields = is_int($parameter) ? array_values($this->bindingFields) : $this->bindingFields;

        return $fields[$parameter] ?? null;
    }

    public function parentOfParameter(string $parameter): ?string
    {
        $key = array_search($parameter, array_keys($this->parameters));

        if ($key === 0 || $key === false) {
            return null;
        }

        return array_values($this->parameters)[$key - 1];
    }

    public function allowsTrashedBindings(): bool
    {
        return $this->withTrashedBindings;
    }

    public function bind(RequestInterface $request): static
    {
        $this->parameters = (new RouteParameterBinder($this))
            ->parameters($request);

        return $this;
    }

    public function signatureParameters(array $conditions = []): array
    {
        return RouteSignatureParameters::fromAction($this->action, $conditions);
    }

    public function getCompiled(): ?RouteCompiled
    {
        return $this->compiled;
    }

    public function setContainer(ApplicationInterface $container): void
    {
        $this->container = $container;
    }
}
