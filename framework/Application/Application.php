<?php

namespace Framework\Kernel\Application;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Container\Container;
use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\Commands\EventServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\RoutingServiceServiceProvider;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Requests\Request;
use Framework\Kernel\Support\Str;

class Application extends Container implements ApplicationInterface
{
    protected ?string $namespace = null;

    protected string $basePath = '';

    protected string $bootstrapPath = '';

    protected string $appPath = '';

    protected string $configPath = '';

    protected string $databasePath = '';

    protected string $publicPath = '';

    protected string $storagePath = '';

    protected string $langPath = '';
    protected array $serviceProviders = [];

    protected array $deferredServices = [];

    protected array $bootingCallbacks = [];

    protected array $bootedCallbacks = [];

    protected bool $hasBeenBootstrapped = false;

    protected bool $booted = false;

    protected array $absoluteCachePathPrefixes = ['/', '\\'];

    public function __construct(string $basePath)
    {
        $this->setBasePath($basePath);

        static::setInstance($this);

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    public function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance(ApplicationInterface::class, $this);

        $this->instance('app', $this);
    }

    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped;
    }

    protected function registerBaseServiceProviders(): void
    {
        $this->register(new EventServiceProvider($this));
        $this->register(new RoutingServiceServiceProvider($this));
    }

    public function register(ServiceProvider|string $provider): ServiceProvider
    {
        if (($registered = $this->getProvider($provider))) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        $this->markAsRegistered($provider);

        $provider->register();

        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    protected function getProvider(ServiceProvider|string $provider): ?ServiceProvider
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        foreach ($this->serviceProviders as $serviceProvider) {
            if (get_class($serviceProvider) === $name) {
                return $serviceProvider;
            }
        }

        return null;
    }

    public function loadDeferredProviders(): void
    {
        foreach ($this->deferredServices as $service => $provider) {
            $this->loadDeferredProvider($service);
        }

        $this->deferredServices = [];
    }

    public function loadDeferredProvider($service): void
    {
        $provider = $this->deferredServices[$service];

        if (! isset($this->loadedProviders[$provider])) {
            $this->registerDeferredProvider($provider, $service);
        }
    }

    public function registerDeferredProvider($provider, $service = null): void
    {
        if ($service) {
            unset($this->deferredServices[$service]);
        }

        $this->register($instance = new $provider($this));

        if (! $this->isBooted()) {
            $this->booting(function () use ($instance) {
                $this->bootProvider($instance);
            });
        }
    }

    public function markAsRegistered(ServiceProvider $provider): void
    {
        $this->serviceProviders[] = $provider;
    }

    public function resolveProvider(string $provider): ServiceProvider
    {
        return new $provider($this);
    }

    public function bootstrapWith(array $bootstrappers): void
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {

            $bootstrapper = new $bootstrapper($this);
            $bootstrapper->bootstrap($this);
        }
    }

    public function registerConfiguredProviders(): void
    {
        $providers = $this->make('config')->get('app.providers');

        foreach ($providers as $provider) {
            $provider = new $provider(static::$instance);
            $this->register($provider);
        }
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function booted(callable $callback): void
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $callback($this);
        }
    }

    public function boot(): void
    {
        if ($this->isBooted()) {
            return;
        }

        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function (ServiceProvider $provider) {
            $this->bootProvider($provider);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    protected function fireAppCallbacks(array &$callbacks): void
    {
        $index = 0;

        while ($index < count($callbacks)) {
            $callbacks[$index]($this);

            $index++;
        }
    }

    protected function bootProvider(ServiceProvider $provider): void
    {
        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    protected function bindPathsInContainer(): void
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.public', $this->publicPath());
        $this->instance('path.resources', $this->resourcePath());
        $this->instance('path.storage', $this->storagePath());

        $this->useLangPath(value(function () {
            return is_dir($directory = $this->resourcePath('lang'))
                ? $directory
                : $this->basePath('lang');
        }));
    }

    public function useLangPath(string $path): static
    {
        $this->langPath = $path;

        $this->instance('path.lang', $path);

        return $this;
    }

    public function storagePath(string $path = ''): string
    {
        if (isset($_ENV['LARAVEL_STORAGE_PATH'])) {
            return $this->joinPaths($this->storagePath ?: $_ENV['LARAVEL_STORAGE_PATH'], $path);
        }

        return $this->joinPaths($this->storagePath ?: $this->basePath('storage'), $path);
    }

    public function basePath(string $path = ''): string
    {
        return $this->joinPaths($this->basePath, $path);
    }

    public function joinPaths(string $basePath, string $path = ''): string
    {
        return $basePath.($path != '' ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    public function setBasePath(string $basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    public function path(string $path = ''): string
    {
        return $this->joinPaths($this->appPath ?: $this->basePath('app'), $path);
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->joinPaths($this->basePath('resources'), $path);
    }

    public function publicPath(string $path = ''): string
    {
        return $this->joinPaths($this->publicPath ?: $this->basePath('public'), $path);
    }

    public function databasePath(string $path = ''): string
    {
        return $this->joinPaths($this->databasePath ?: $this->basePath('database'), $path);
    }

    public function configPath(string $path = ''): string
    {
        return $this->joinPaths($this->configPath ?: $this->basePath('config'), $path);
    }

    //    public function getCachedConfigPath(): string
    //    {
    //        return $this->normalizeCachePath('APP_CONFIG_CACHE', 'cache/config.php');
    //    }

    //    public function bootstrapPath($path = ''): string
    //    {
    //        return $this->joinPaths($this->bootstrapPath, $path);
    //    }
    //
    //    protected function normalizeCachePath(string $key,string $default): string
    //    {
    //        if (is_null($env = Env::get($key))) {
    //            return $this->bootstrapPath($default);
    //        }
    //
    //        return Str::startsWith($env, $this->absoluteCachePathPrefixes)
    //            ? $env
    //            : $this->basePath($env);
    //    }

    public function booting(callable $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    public function registerCoreContainerAliases(): void
    {
        foreach ([
            'app' => [self::class, ApplicationInterface::class],
            'request' => [RequestInterface::class, Request::class],
        ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    public function getNamespace(): string
    {
        if (! is_null($this->namespace)) {
            return $this->namespace;
        }

        $composer = json_decode(file_get_contents($this->basePath('composer.json')), true);

        foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            foreach ((array) $path as $pathChoice) {
                if (realpath($this->path()) === realpath($this->basePath($pathChoice))) {
                    return $this->namespace = $namespace;
                }
            }
        }

        throw new \Exception('Unable to detect application namespace.');
    }

    public function getLocale(): string
    {
        return $this['config']['app.locale'];
    }

    public function getFallbackLocale(): string
    {
        return $this['config']->get('app.fallback_locale');
    }
}
