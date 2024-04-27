<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Cache\Contracts\CacheFactoryInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\Session\Middleware\StartSession;
use Framework\Kernel\Session\SessionManager;

class SessionServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->registerSessionManager();

        $this->registerSessionDriver();

        $this->app->singleton(StartSession::class, function (ApplicationInterface $app) {
            return new StartSession($app->make(SessionManager::class), function () use ($app) {
                return $app->make(CacheFactoryInterface::class);
            });
        });
    }

    protected function registerSessionManager(): void
    {
        $this->app->singleton('session', function (ApplicationInterface $app) {
            return new SessionManager($app);
        });

        $this->app->alias('session',SessionManager::class);
    }

    protected function registerSessionDriver(): void
    {
        $this->app->singleton('session.store', function (ApplicationInterface $app) {
            return $app->make('session')->driver();
        });
    }
}