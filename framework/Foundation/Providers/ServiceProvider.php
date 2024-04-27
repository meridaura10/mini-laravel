<?php

namespace Framework\Kernel\Foundation\Providers;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Console\Artisan\Artisan;
use Framework\Kernel\Console\Contracts\ArtisanInterface;
use Framework\Kernel\Foundation\Providers\Contracts\DefaultProvidersInterface;

abstract class ServiceProvider
{
    protected array $bootedCallbacks = [];

    public function __construct(
        protected ApplicationInterface $app,
    ) {

    }

    abstract public function register(): void;

    public function booted(\Closure $callback): void
    {
        $this->bootedCallbacks[] = $callback;
    }

    public function callBootedCallbacks(): void
    {
        foreach ($this->bootedCallbacks as $bootedCallback) {
            $this->app->call($bootedCallback);
        }
    }

    protected function loadViewsFrom(array|string $path,string $namespace)
    {
//        $this->callAfterResolving('view', function ($view) use ($path, $namespace) {
//            if (isset($this->app->config['view']['paths']) &&
//                is_array($this->app->config['view']['paths'])) {
//                foreach ($this->app->config['view']['paths'] as $viewPath) {
//                    if (is_dir($appPath = $viewPath.'/vendor/'.$namespace)) {
//                        $view->addNamespace($namespace, $appPath);
//                    }
//                }
//            }
//
//            $view->addNamespace($namespace, $path);
//        });
    }

    protected function callAfterResolving(string $name,callable $callback): void
    {
        $this->app->afterResolving($name, $callback);

        if ($this->app->resolved($name)) {
            $callback($this->app->make($name), $this->app);
        }
    }

    public function commands(mixed $commands): void
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        Artisan::starting(function (ArtisanInterface $artisan) use ($commands) {
            $artisan->resolveCommands($commands);
        });
    }

    public static function defaultProviders(): DefaultProvidersInterface
    {
        return new DefaultProviders();
    }


}
