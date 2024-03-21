<?php

namespace Framework\Kernel\Foundation\Providers;

class AggregateServiceProvider extends ServiceProvider
{
    protected array $providers = [];

    protected array $instances = [];

    public function register(): void
    {
        $this->instances = [];

        foreach ($this->providers as $provider) {
            $this->instances[] = $this->app->register($provider);
        }
    }

    public function provides(): array
    {
        $provides = [];

        foreach ($this->providers as $provider) {
            $instance = $this->app->resolveProvider($provider);

            $provides = array_merge($provides, $instance->provides());
        }

        return $provides;
    }
}
