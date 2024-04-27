<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\Hashing\Contracts\HasherInterface;
use Framework\Kernel\Hashing\HashManager;

class HashServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton('hash', function ($app) {
            return new HashManager($app);
        });

        $this->app->alias('hash', HasherInterface::class);

        $this->app->singleton('hash.driver', function ($app) {
            return $app['hash']->driver();
        });
    }
}