<?php

namespace Framework\Kernel\Http;

use Framework\Kernel\Route\Middleware\SubstituteBindings;

class Kernel extends KernelHttp
{
    protected array $middleware = [

    ];

    protected array $middlewareGroups = [
        'web' => [
            \Framework\Kernel\Route\Middleware\SubstituteBindings::class,
        ],
        'api' => [
            \Framework\Kernel\Route\Middleware\SubstituteBindings::class,
        ],
    ];

    protected array $middlewareAliases = [

    ];
}
