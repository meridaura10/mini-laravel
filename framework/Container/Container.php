<?php

namespace Framework\Kernel\Container;

use ArrayAccess;
use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Container\Contracts\ContainerInterface;
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

    protected array $buildStack = [];

    protected array $resolvingCallbacks = [];

    public function bind(string $abstraction, callable|string $concrete, $shared = false): void
    {
        $this->bindings[$abstraction] = compact('concrete', 'shared');
    }

    public function singleton(string $abstraction, callable|string $concrete): void
    {
        $this->bind($abstraction, $concrete, true);
    }

    public function make(string $abstraction): mixed
    {
        return $this->resolve($abstraction);
    }


    public function resolve(string $abstraction, bool $raiseEvents = true): mixed
    {
        try {

            $abstraction = $this->getAlias($abstraction);

            $concrete = $this->getConcrete($abstraction);

            if (isset($this->instances[$abstraction])) {
                return $this->instances[$abstraction];
            }

            if (! $concrete) {
                throw new \Exception("$abstraction not concrete");
            }

            $object = $this->build($concrete);

            if ($this->isShared($abstraction)) {
                $this->instance($abstraction, $object);
            }

            if ($raiseEvents) {
                $this->fireResolvingCallbacks($abstraction, $object);
            }

            return $object;
        } catch (\Exception $exception) {
            dd($exception->getMessage(), 'error resolve '.$abstraction);
        }
    }

    protected function fireResolvingCallbacks(string $abstraction, mixed $object): void
    {
        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstraction, $object, $this->resolvingCallbacks)
        );
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
        try {
            if (is_callable($concrete)) {
                return $concrete($this);
            }

            $reflector = new ReflectionClass($concrete);

            return $this->reflectionBuild($reflector, $concrete);
        } catch (\Exception $exception) {
            dd($exception->getMessage());
            dd('error to method build in container concrete = '.$concrete, $exception->getMessage());
        }
    }

    private function reflectionBuild($reflector, $concrete = null)
    {
        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return $reflector->newInstance();
        }

        $parameters = $constructor->getParameters();

        $dependencies = $this->resolveDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    private function resolveDependencies($parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {

            $result = is_null($this->getParameterClassName($parameter))
                ? $this->resolvePrimitive($parameter)
                : $this->resolveClass($parameter);

            $dependencies[] = $result;
        }

        return $dependencies;
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

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
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

        if (($className = $this->getClassForCallable($callback)) && ! in_array(
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
            ! ($reflector = new ReflectionFunction($callback(...)))->isAnonymous()) {
            return $reflector->getClosureScopeClass()->name ?? false;
        }

        if (! is_array($callback)) {
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

    public function bound($abstract): bool
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
        $this->bind($offset, $value instanceof Closure ? $value : fn () => $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->bindings[$offset], $this->instances[$offset]);
    }
}
