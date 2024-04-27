<?php

namespace Framework\Kernel\Http\Cookie\Middleware;


use Framework\Kernel\Http\Cookie\Contracts\QueueingFactoryInterface;

class AddQueuedCookiesToResponseMiddleware
{
    public function __construct(
      protected QueueingFactoryInterface $cookies
    ) {

    }

    public function handle($request, \Closure $next): mixed
    {
        $response = $next($request);

        foreach ($this->cookies->getQueuedCookies() as $cookie){
            $response->headers->setCookie($cookie);
        }

        return $response;
    }
}