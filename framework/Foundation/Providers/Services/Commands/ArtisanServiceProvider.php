<?php

namespace Framework\Kernel\Foundation\Providers\Services\Commands;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Console\Commands\Artisan\Dev\ControllerMakeCommand;
use Framework\Kernel\Console\Commands\Artisan\Dev\FactoryMakeCommand;
use Framework\Kernel\Console\Commands\Artisan\Dev\ModelMakeCommand;
use Framework\Kernel\Console\Commands\Artisan\Dev\RequestMakeCommand;
use Framework\Kernel\Console\Commands\Artisan\Dev\ServeCommand;
use Framework\Kernel\Console\Commands\Artisan\Prod\Storage\StorageLinkCommand;
use Framework\Kernel\Console\Commands\Database\Seeders\SeedCommand;
use Framework\Kernel\Console\Commands\Database\Seeders\SeederMakeCommand;
use Framework\Kernel\Foundation\Providers\Contracts\DeferrableProviderInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

class ArtisanServiceProvider extends ServiceProvider implements DeferrableProviderInterface
{
    protected array $commands = [
        'Seed' => SeedCommand::class,
        'StorageLink' => StorageLinkCommand::class,
    ];

    protected array $devCommands = [
        'ControllerMake' => ControllerMakeCommand::class,
        'RequestMake' => RequestMakeCommand::class,
        'ModelMake' => ModelMakeCommand::class,
        'SeederMake' => SeederMakeCommand::class,
        'FactoryMake' => FactoryMakeCommand::class,
        'Serve' => ServeCommand::class,
    ];

    public function register(): void
    {
        $this->registerCommands(array_merge(
            $this->commands,
            $this->devCommands
        ));
    }

    protected function registerSeedCommand(): void
    {
        $this->app->singleton(SeedCommand::class, function ($app) {
            return new SeedCommand($app['db']);
        });
    }

    protected function registerCommands(array $commands): void
    {
        foreach ($commands as $commandName => $command) {
            $method = "register{$commandName}Command";

            if (method_exists($this, $method)) {
                $this->{$method}();
            } else {
                $this->app->singleton($command, function (ApplicationInterface $app) use ($command) {
                    return new $command($app['files']);
                });
            }
        }

        $this->commands(array_values($commands));
    }

    public function provides(): array
    {
        return array_merge(array_values($this->commands), array_values($this->devCommands));
    }
}
