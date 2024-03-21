<?php

namespace Framework\Kernel\Foundation\Providers;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Console\Artisan\Artisan;
use Framework\Kernel\Console\Contracts\ArtisanInterface;
use Framework\Kernel\Foundation\Providers\Contracts\DefaultProvidersInterface;

abstract class ServiceProvider
{
    protected array $bootedCallbacks = [];

    public function __construct(
        protected ApplicationInterface $app,
    ) {

    }

    abstract public function register(): void;

    public function booted(\Closure $callback): void
    {
        $this->bootedCallbacks[] = $callback;
    }

    public function callBootedCallbacks(): void
    {
        foreach ($this->bootedCallbacks as $bootedCallback) {
            $this->app->call($bootedCallback);
        }
    }

    public static function defaultProviders(): DefaultProvidersInterface
    {
        return new DefaultProviders();
    }

    public function commands(mixed $commands): void
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        Artisan::starting(function (ArtisanInterface $artisan) use ($commands) {
            $artisan->resolveCommands($commands);
        });
    }
}
