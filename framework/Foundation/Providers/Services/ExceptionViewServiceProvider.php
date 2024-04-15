<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Foundation\Exceptions\Contracts\ExceptionPageInterface;
use Framework\Kernel\Foundation\Exceptions\Contracts\ExceptionRendererInterface;
use Framework\Kernel\Foundation\Exceptions\ExceptionPage;
use Framework\Kernel\Foundation\Exceptions\ExceptionRenderer;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

class ExceptionViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerExceptionPage();
        $this->registerExceptionRender();
    }

    protected function registerExceptionRender(): void
    {
        $this->app->bind(ExceptionRendererInterface::class,function (ApplicationInterface $app){
            return new ExceptionRenderer($app['exceptionPage']);
        });

        $this->booted(function (){
            view()->getFinder()->addNamespace('error', __DIR__ . "../../../Exceptions/resources/views");
        });
    }

    protected function registerExceptionPage(): void
    {
        $this->app->bind('exceptionPage',function (ApplicationInterface $app){
            return new ExceptionPage();
        });
    }
}