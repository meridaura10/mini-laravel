<?php

namespace Framework\Kernel\Http;

use Framework\Kernel\Route\Middleware\SubstituteBindings;
use Framework\Kernel\Session\Middleware\StartSession;

class Kernel extends KernelHttp
{
    protected array $middleware = [

    ];

    protected array $middlewareGroups = [
        'web' => [
            \Framework\Kernel\Http\Cookie\Middleware\AddQueuedCookiesToResponseMiddleware::class,
            \Framework\Kernel\Session\Middleware\StartSession::class,
            \Framework\Kernel\View\Middleware\ShareErrorsFromSessionMiddleware::class,
            \Framework\Kernel\Route\Middleware\SubstituteBindings::class,
        ],
        'api' => [
            \Framework\Kernel\Route\Middleware\SubstituteBindings::class,
        ],
    ];

    protected array $middlewareAliases = [

    ];
}
