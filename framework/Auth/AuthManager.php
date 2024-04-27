<?php

namespace Framework\Kernel\Auth;

use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Auth\Contracts\AuthFactoryInterface;
use Framework\Kernel\Auth\Contracts\AuthGuardInterface;
use Framework\Kernel\Auth\Contracts\AuthStatefulGuardInterface;
use Framework\Kernel\Auth\Guards\SessionGuard;
use Framework\Kernel\Auth\Traits\CreatesUserProvidersTrait;
use InvalidArgumentException;

class AuthManager implements AuthFactoryInterface
{
    use CreatesUserProvidersTrait;

    protected Closure $userResolver;

    protected array $guards = [];

    protected array $customCreators = [];

    public function __construct(
        protected ApplicationInterface $app,
    ) {
        $this->userResolver = fn ($guard = null) => $this->guard($guard)->user();  
    }

    public function guard(?string $name = null): AuthGuardInterface|AuthStatefulGuardInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->guards[$name] ?? $this->guards[$name] = $this->resolve($name);
    }

    public function resolve(string $name): AuthGuardInterface|AuthStatefulGuardInterface
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($name, $config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($name, $config);
        }

        throw new InvalidArgumentException(
            "Auth driver [{$config['driver']}] for guard [{$name}] is not defined."
        );
    }

    public function createSessionDriver(string $name,array $config): SessionGuard
    {
        $provider = $this->createUserProvider($config['provider'] ?? null);

        $guard = new SessionGuard(
            $name,
            $provider,
            $this->app['session.store'],
        );

        if (method_exists($guard, 'setCookieJar')) {
            $guard->setCookieJar($this->app['cookie']);
        }

        if (method_exists($guard, 'setDispatcher')) {
            $guard->setDispatcher($this->app['events']);
        }

        if (method_exists($guard, 'setRequest')) {
            $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
        }

        if (isset($config['remember'])) {
            $guard->setRememberDuration($config['remember']);
        }

        return $guard;

    }

    public function getConfig(string $name): ?array
    {
        return $this->app['config']["auth.guards.{$name}"];
    }

    public function getDefaultDriver(): string
    {
        return $this->app['config']['auth.defaults.guard'];
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->guard()->{$method}(...$parameters);
    }
}