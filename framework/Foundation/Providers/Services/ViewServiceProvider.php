<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\View\Blade\DynamicComponent;
use Framework\Kernel\View\Compilers\BladeCompiler;
use Framework\Kernel\View\Contracts\EngineResolverInterface;
use Framework\Kernel\View\Contracts\FileViewFinderInterface;
use Framework\Kernel\View\Contracts\ViewFactoryInterface;
use Framework\Kernel\View\EngineResolver;
use Framework\Kernel\View\Engines\CompilerEngine;
use Framework\Kernel\View\Engines\PhpEngine;
use Framework\Kernel\View\FileViewFinder;
use Framework\Kernel\View\ViewFactory;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerFactory();
        $this->registerViewFinder();
        $this->registerBladeCompiler();
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
            $this->registerBladeEngine($resolver);

            return $resolver;
        });
    }

    public function registerBladeEngine(EngineResolver $resolver): void
    {
        $resolver->register('blade', function () {
            return new CompilerEngine($this->app['blade.compiler'], $this->app['files']);
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


    public function registerBladeCompiler(): void
    {
        $this->app->singleton('blade.compiler', function ($app) {
            return tap(new BladeCompiler(
                $app['files'],
                $app['config']['view.compiled'],
                $app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
                $app['config']->get('view.cache', true),
                $app['config']->get('view.compiled_extension', 'php'),
            ), function (BladeCompiler $blade) {
//                $blade->component('dynamic-component', DynamicComponent::class);
            });
        });
    }
}
