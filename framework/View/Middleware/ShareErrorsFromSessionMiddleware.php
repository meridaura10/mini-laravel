<?php

namespace Framework\Kernel\View\Middleware;

use Closure;
use Framework\Kernel\Support\ViewErrorBag;
use Framework\Kernel\View\Contracts\ViewFactoryInterface;

class ShareErrorsFromSessionMiddleware
{
    public function __construct(
        protected ViewFactoryInterface $view,
    )
    {

    }

    public function handle($request, Closure $next)
    {
        $this->view->share(
            'errors', $request->session()->get('errors') ?: new ViewErrorBag
        );


        return $next($request);
    }
}