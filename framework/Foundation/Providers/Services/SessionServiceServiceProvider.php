<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\Session\Contracts\SessionInterface;
use Framework\Kernel\Session\Session;

class SessionServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('session', Session::class);

        $this->app->alias('session', SessionInterface::class);
    }
}
