<?php

namespace App\Http\Middleware;

class Middleware
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}
