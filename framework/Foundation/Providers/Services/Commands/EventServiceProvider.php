<?php

namespace Framework\Kernel\Foundation\Providers\Services\Commands;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Events\Contracts\DispatcherInterface;
use Framework\Kernel\Events\Dispatcher;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('events', function (ApplicationInterface $app) {
            return new Dispatcher($app);
        });

        $this->app->alias('events', DispatcherInterface::class);
    }
}
