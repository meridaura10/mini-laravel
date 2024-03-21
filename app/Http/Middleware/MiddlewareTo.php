<?php

namespace App\Http\Middleware;

class MiddlewareTo
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}
