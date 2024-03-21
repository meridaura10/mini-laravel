<?php

namespace App\Providers;

use Framework\Kernel\Facades\Services\Route;
use Framework\Kernel\Foundation\Providers\Services\RouteServiceServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->routes(function () {

            Route::middleware('api')->prefix('api/')
                ->name('api.')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

        });
    }
}
