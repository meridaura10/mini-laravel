<?php

namespace App\Http\Middleware;

class MiddlewareG3
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}
