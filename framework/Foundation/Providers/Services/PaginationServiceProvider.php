<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Database\Pagination\PaginationState;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

class PaginationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'pagination');
//
//        if ($this->app->runningInConsole()) {
//            $this->publishes([
//                __DIR__.'/resources/views' => $this->app->resourcePath('views/vendor/pagination'),
//            ], 'laravel-pagination');
//        }
    }

    public function register(): void
    {
        PaginationState::resolveUsing($this->app);
    }
}