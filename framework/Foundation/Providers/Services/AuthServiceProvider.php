<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Auth\AuthManager;
use Framework\Kernel\Auth\Contracts\AuthFactoryInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->registerAuthenticator();
    }

    protected function registerAuthenticator(): void
    {
        $this->app->singleton('auth', fn(ApplicationInterface $app) => new AuthManager($app));
        $this->app->alias('auth', AuthFactoryInterface::class);

        $this->app->singleton('auth.driver', fn ($app) => $app['auth']->guard());
    }
}