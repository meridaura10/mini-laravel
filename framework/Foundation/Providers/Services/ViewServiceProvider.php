<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\View\Contracts\EngineResolverInterface;
use Framework\Kernel\View\Contracts\FileViewFinderInterface;
use Framework\Kernel\View\Contracts\ViewFactoryInterface;
use Framework\Kernel\View\EngineResolver;
use Framework\Kernel\View\Engines\PhpEngine;
use Framework\Kernel\View\FileViewFinder;
use Framework\Kernel\View\ViewFactory;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerFactory();
        $this->registerViewFinder();
        $this->registerEngineResolver();
    }

    private function registerViewFinder(): void
    {
        $this->app->alias('view.finder', FileViewFinderInterface::class);

        $this->app->bind('view.finder', function (ApplicationInterface $app) {
            return new FileViewFinder($app->make('files'), $app->make('config')->get('view.paths'));
        });
    }

    private function registerEngineResolver(): void
    {
        $this->app->alias('view.engine.resolver', EngineResolverInterface::class);
        $this->app->singleton('view.engine.resolver', function () {
            $resolver = new EngineResolver;

            $this->registerPhpEngine($resolver);

            return $resolver;
        });
    }

    protected function registerPhpEngine(EngineResolverInterface $resolver): void
    {
        $resolver->register('php', function () {
            return new PhpEngine($this->app->make('files'));
        });
    }

    private function registerFactory(): void
    {
        $this->app->alias('view', ViewFactoryInterface::class);
        $this->app->singleton('view', function ($app) {
            $resolver = $app['view.engine.resolver'];

            $finder = $app['view.finder'];

            $factory = new ViewFactory($resolver, $finder);

            $factory->setContainer($app);

            $factory->share('app', $app);

            return $factory;
        });
    }
}
