<?php

namespace Framework\Kernel\Foundation\Providers\Services\Commands;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Foundation\Providers\Contracts\DeferrableProviderInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\Support\Composer;

class ComposerServiceProvider extends ServiceProvider implements DeferrableProviderInterface
{
    public function register(): void
    {
        $this->app->singleton('composer',function (ApplicationInterface $app){
            return new Composer($app['files'],$app->basePath());
        });
    }

    public function provides(): array
    {
        return ['composer'];
    }
}