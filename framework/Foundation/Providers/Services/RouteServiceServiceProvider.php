<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Closure;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

class RouteServiceServiceProvider extends ServiceProvider
{
    protected ?Closure $loadRoutesUsing = null;

    public function register(): void
    {
        $this->booted(function () {
            $this->loadRoutes();
        });
    }

    protected function loadRoutes(): void
    {
        if (! is_null($this->loadRoutesUsing)) {
            $this->app->call($this->loadRoutesUsing);
        }
    }

    protected function routes(\Closure $routesCallback): static
    {
        $this->loadRoutesUsing = $routesCallback;

        return $this;
    }
}
