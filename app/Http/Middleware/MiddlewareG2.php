<?php

namespace App\Http\Middleware;

class MiddlewareG2
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}
