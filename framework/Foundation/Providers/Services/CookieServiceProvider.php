<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\Http\Cookie\Contracts\QueueingFactoryInterface;
use Framework\Kernel\Http\Cookie\CookieJar;

class CookieServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton('cookie', function ($app) {
            $config = $app->make('config')->get('session');

            return (new CookieJar())->setDefaultPathAndDomain(
                $config['path'], $config['domain'], $config['secure'], $config['same_site'] ?? null
            );
        });

        $this->app->alias('cookie', QueueingFactoryInterface::class);
    }
}