<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\Route\Contracts\RouterInterface;
use Framework\Kernel\Route\Router;

class RoutingServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerRouter();
    }

    protected function registerRouter(): void
    {
        $this->app->singleton('router', Router::class);

        $this->app->alias('router', RouterInterface::class);
    }
}
