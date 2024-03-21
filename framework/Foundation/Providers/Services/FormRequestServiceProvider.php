<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Requests\FormRequest;

class FormRequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->resolving(FormRequest::class, function (RequestInterface $request, ApplicationInterface $app) {
            $request = FormRequest::createFrom($app->make('request'), $request);

            $request->setContainer($app);
        });
    }
}
