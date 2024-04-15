<?php

use App\Providers\RouteServiceProvider;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

return [
    'debug' => true,
    'locale' => 'en',
    'fallback_locale' => 'en',
    'providers' => ServiceProvider::defaultProviders()->merge([
        RouteServiceProvider::class,
    ])->toArray(),
];
