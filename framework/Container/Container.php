<?php

namespace Framework\Kernel\Container;

use ArrayAccess;
use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Container\Contracts\ContainerInterface;
use Framework\Kernel\Database\Pagination\LengthAwarePaginator;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

class Container implements ArrayAccess, ContainerInterface
{
    protected static ApplicationInterface $instance;

    protected array $bindings = [];

    protected array $aliases = [];

    protected array $abstractAliases = [];

    protected array $instances = [];

    protected array $resolved = [];

    protected array $buildStack = [];

    protected array $with = [];

    protected array $resolvingCallbacks = [];

    protected array $globalResolvingCallbacks = [];

    protected array $globalAfterResolvingCallbacks = [];

    protected array $afterResolvingCallbacks = [];

    protected array $reboundCallbacks = [];


    public function bind(string $abstraction, callable|string $concrete, $shared = false): void
    {
        $this->bindings[$abstraction] = compact('concrete', 'shared');
    }

    public function singleton(string $abstraction, callable|string $concrete): void
    {
        $this->bind($abstraction, $concrete, true);
    }

    public function make(string $abstraction, array $parameters = []): mixed
    {
        return $this->resolve($abstraction, $parameters);
    }


    public function resolve(string $abstraction, array $parameters = [], bool $raiseEvents = true): mixed
    {
        $abstraction = $this->getAlias($abstraction);

        $this->with[] = $parameters;

        $concrete = $this->getConcrete($abstraction);

        if (isset($this->instances[$abstraction])) {
            return $this->instances[$abstraction];
        }

        if (!$concrete) {
            throw new \Exception("$abstraction not concrete");
        }

        $object = $this->build($concrete);

        if ($this->isShared($abstraction)) {
            $this->instance($abstraction, $object);
        }

        if ($raiseEvents) {
            $this->fireResolvingCallbacks($abstraction, $object);
        }

        array_pop($this->with);

        $this->resolved[$abstraction] = true;

        return $object;
    }

    protected function fireResolvingCallbacks(string $abstraction, mixed $object): void
    {
        $this->fireCallbackArray($object, $this->globalResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstraction, $object, $this->resolvingCallbacks)
        );

        $this->fireAfterResolvingCallbacks($abstraction, $object);
    }

    protected function fireAfterResolvingCallbacks(string $abstract, mixed $object): void
    {
        $this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks)
        );
    }

    public function afterResolving(Closure|string $abstract, Closure $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->globalAfterResolvingCallbacks[] = $abstract;
        } else {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }


    protected function getCallbacksForType(string $abstraction, mixed $object, array $callbacksPerType): array
    {
        $results = [];

        foreach ($callbacksPerType as $type => $callbacks) {
            if ($type === $abstraction || $object instanceof $type) {
                $results = array_merge($results, $callbacks);
            }
        }

        return $results;
    }

    protected function fireCallbackArray(mixed $object, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    public function resolving(string $abstraction, ?Closure $callback = null): void
    {
        $abstract = $this->getAlias($abstraction);
        $this->resolvingCallbacks[$abstract][] = $callback;
    }

    private function getConcrete(string $abstraction): callable|string|null
    {
        if (isset($this->bindings[$abstraction])) {
            return $this->bindings[$abstraction]['concrete'];
        }

        return $abstraction;
    }

    public function instance(string $abstraction, mixed $instance): mixed
    {
        $this->instances[$abstraction] = $instance;

        return $instance;
    }

    private function build(callable|string $concrete): mixed
    {
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        $reflector = new ReflectionClass($concrete);

        return $this->reflectionBuild($reflector, $concrete);
    }

    private function reflectionBuild(ReflectionClass $reflector, $concrete = null)
    {
        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return $reflector->newInstance();
        }

        $parameters = $constructor->getParameters();

        $dependencies = $this->resolveDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    public function resolved(string $abstract): bool
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        return isset($this->resolved[$abstract]) ||
            isset($this->instances[$abstract]);
    }

    public function refresh(string $abstract, mixed $target, string $method): mixed
    {
        return $this->rebinding($abstract, function ($app, $instance) use ($target, $method) {
            $target->{$method}($instance);
        });
    }

    public function rebinding(string $abstract, Closure $callback): mixed
    {
        $this->reboundCallbacks[$abstract = $this->getAlias($abstract)][] = $callback;

        if ($this->bound($abstract)) {
            return $this->make($abstract);
        }
    }

    private function resolveDependencies($parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            if ($this->hasParameterOverride($parameter)) {
                $dependencies[] = $this->getParameterOverride($parameter);

                continue;
            }

            $result = is_null($this->getParameterClassName($parameter))
                ? $this->resolvePrimitive($parameter)
                : $this->resolveClass($parameter);

            $dependencies[] = $result;
        }

        return $dependencies;
    }

    protected function hasParameterOverride(ReflectionParameter $dependency): bool
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }

    protected function getParameterOverride(ReflectionParameter $dependency): mixed
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    protected function getLastParameterOverride(): array
    {
        return count($this->with) ? end($this->with) : [];
    }

    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    protected function resolveClass(ReflectionParameter $parameter): mixed
    {
        return $this->make($parameter->getType()->getName());
    }

    protected function resolvePrimitive(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isVariadic()) {
            return [];
        }
    }

    protected function getParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (!is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }

    public function alias(string $abstraction, string $alias): void
    {
        if ($alias === $abstraction) {
            throw new \Exception("[{$abstraction}] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstraction;

        $this->abstractAliases[$abstraction][] = $alias;
    }

    public function getAlias(string $abstract): string
    {
        return $this->isAlias($abstract)
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    private function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    private function isShared(string $abstraction): bool
    {
        return isset($this->instances[$abstraction]) ||
            (isset($this->bindings[$abstraction]['shared']) &&
                $this->bindings[$abstraction]['shared'] === true);
    }

    public function call(callable|string $callback, array $parameters = [], ?string $defaultMethod = null): mixed
    {
        $pushedToBuildStack = false;

        if (($className = $this->getClassForCallable($callback)) && !in_array(
                $className,
                $this->buildStack,
                true
            )) {
            $this->buildStack[] = $className;

            $pushedToBuildStack = true;
        }

        $result = BoundMethod::call($this, $callback, $parameters, $defaultMethod);

        if ($pushedToBuildStack) {
            array_pop($this->buildStack);
        }

        return $result;
    }

    protected function getClassForCallable(callable|string $callback): ?string
    {
        if (is_callable($callback) &&
            !($reflector = new ReflectionFunction($callback(...)))->isAnonymous()) {
            return $reflector->getClosureScopeClass()->name ?? false;
        }

        if (!is_array($callback)) {
            return false;
        }

        return is_string($callback[0]) ? $callback[0] : get_class($callback[0]);
    }

    public static function setInstance(ApplicationInterface $container): ApplicationInterface
    {
        return static::$instance = $container;
    }

    public static function getInstance(): ApplicationInterface
    {
        return static::$instance;
    }

    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            $this->isAlias($abstract);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->bound($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->make($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->bind($offset, $value instanceof Closure ? $value : fn() => $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->bindings[$offset], $this->instances[$offset]);
    }
}
