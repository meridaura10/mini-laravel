<?php

namespace Framework\Kernel\Foundation\Providers\Services\Commands;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Console\Commands\Artisan\Dev\ControllerMakeCommand;
use Framework\Kernel\Foundation\Providers\Contracts\DeferrableProviderInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

class ArtisanServiceProvider extends ServiceProvider implements DeferrableProviderInterface
{
    protected array $commands = [

    ];

    protected array $devCommands = [
        'ControllerMake' => ControllerMakeCommand::class,
    ];

    public function register(): void
    {
        $this->registerCommands(array_merge(
            $this->commands,
            $this->devCommands
        ));
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
