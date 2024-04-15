<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Requests\Contracts\ValidatesWhenResolvedInterface;
use Framework\Kernel\Http\Requests\FormRequest;

class FormRequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->afterResolving(ValidatesWhenResolvedInterface::class, function (ValidatesWhenResolvedInterface $resolved) {
            $resolved->validateResolved();
        });

        $this->app->resolving(FormRequest::class, function ($request, $app) {
            $request = FormRequest::createFrom($app['request'], $request);
//            ->setRedirector($app->make(Redirector::class))
            $request->setContainer($app);
        });
    }
}
