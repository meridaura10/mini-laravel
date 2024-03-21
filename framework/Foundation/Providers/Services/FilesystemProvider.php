<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Filesystem\Contracts\FilesystemCloudInterface;
use Framework\Kernel\Filesystem\Contracts\filesystemDiskInterface;
use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;
use Framework\Kernel\Filesystem\Contracts\FilesystemManagerInterface;
use Framework\Kernel\Filesystem\Filesystem;
use Framework\Kernel\Filesystem\FilesystemManager;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

class FilesystemProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerNativeFilesystem();

        $this->registerFlysystem();
    }

    protected function registerNativeFilesystem(): void
    {
        $this->app->singleton('files', Filesystem::class);

        $this->app->alias('files', FilesystemInterface::class);
    }

    protected function registerFlysystem(): void
    {
        $this->registerManager();

        $this->app->singleton('filesystem.disk', function ($app) {
            return $app['filesystem']->disk($this->getDefaultDriver());
        });

        $this->app->alias('filesystem.disk', filesystemDiskInterface::class);

        $this->app->singleton('filesystem.cloud', function ($app) {
            return $app['filesystem']->disk($this->getCloudDriver());
        });

        $this->app->alias('filesystem.cloud', FilesystemCloudInterface::class);
    }

    protected function registerManager(): void
    {
        $this->app->singleton('filesystem', function (ApplicationInterface $app) {
            return new FilesystemManager($app);
        });

        $this->app->alias('filesystem', FilesystemManagerInterface::class);
    }

    protected function getDefaultDriver(): string
    {
        return $this->app['config']['filesystems.default'];
    }

    protected function getCloudDriver(): string
    {
        return $this->app['config']['filesystems.cloud'];
    }
}
