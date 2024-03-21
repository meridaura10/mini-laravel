<?php

namespace App\Http\Middleware;

class MiddlewareP1
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}
