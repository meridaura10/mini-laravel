<?php

use App\Providers\RouteServiceProvider;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

return [
    'providers' => ServiceProvider::defaultProviders()->merge([
        RouteServiceProvider::class,
    ])->toArray(),
];
