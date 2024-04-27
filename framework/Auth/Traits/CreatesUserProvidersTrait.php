<?php

namespace Framework\Kernel\Auth\Traits;

use Framework\Kernel\Auth\Contracts\AuthUserProviderInterface;
use Framework\Kernel\Auth\UserProviders\EloquentUserProvider;
use InvalidArgumentException;

trait CreatesUserProvidersTrait
{
    protected array $customProviderCreators = [];

    public function createUserProvider(?string $provider = null): ?AuthUserProviderInterface
    {
        if (is_null($config = $this->getProviderConfiguration($provider))) {
            return null;
        }

        if (isset($this->customProviderCreators[$driver = ($config['driver'] ?? null)])) {
            return call_user_func(
                $this->customProviderCreators[$driver], $this->app, $config
            );
        }

        return match ($driver) {
            'database' => $this->createDatabaseProvider($config),
            'eloquent' => $this->createEloquentProvider($config),
            default => throw new InvalidArgumentException(
                "Authentication user provider [{$driver}] is not defined."
            ),
        };
    }

    protected function createEloquentProvider(array $config): EloquentUserProvider
    {
        return new EloquentUserProvider($this->app['hash'], $config['model']);
    }

    protected function getProviderConfiguration(?string $provider): ?array
    {
        if ($provider = $provider ?: $this->getDefaultUserProvider()) {
            return $this->app['config']['auth.providers.'.$provider];
        }
    }

    public function getDefaultUserProvider(): string
    {
        return $this->app['config']['auth.defaults.provider'];
    }
}