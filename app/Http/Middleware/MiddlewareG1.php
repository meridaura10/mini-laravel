<?php

namespace App\Http\Middleware;

class MiddlewareG1
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}
