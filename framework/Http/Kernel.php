<?php

namespace Framework\Kernel\Http;

class Kernel extends KernelHttp
{
    protected array $middleware = [
        \App\Http\Middleware\Middleware::class,
        \App\Http\Middleware\MiddlewareTo::class,
    ];

    protected array $middlewareGroups = [
        'api' => [
            \App\Http\Middleware\MiddlewareG1::class,
            \App\Http\Middleware\MiddlewareG2::class,
            \App\Http\Middleware\MiddlewareG3::class,
        ],
    ];

    protected array $middlewareAliases = [
        'p1' => \App\Http\Middleware\MiddlewareP1::class,
    ];
}
