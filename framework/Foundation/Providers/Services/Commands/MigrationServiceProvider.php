<?php

namespace Framework\Kernel\Foundation\Providers\Services\Commands;

use Framework\Kernel\Application\Contracts\ApplicationInterface;

use Framework\Kernel\Console\Commands\Database\Migrations\MigrateCommand;
use Framework\Kernel\Console\Commands\Database\Migrations\MigrateInstallCommand;
use Framework\Kernel\Console\Commands\Database\Migrations\MigrateMakeCommand;
use Framework\Kernel\Console\Commands\Database\Migrations\MigrateRefreshCommand;
use Framework\Kernel\Console\Commands\Database\Migrations\MigrateResetCommand;
use Framework\Kernel\Database\Migrations\DatabaseMigrationRepository;
use Framework\Kernel\Database\Migrations\MigrationCreator;
use Framework\Kernel\Database\Migrations\Migrator;
use Framework\Kernel\Foundation\Providers\Contracts\DeferrableProviderInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider implements DeferrableProviderInterface
{
    protected array $commands = [
        'Migrate' => MigrateCommand::class,
        'MigrateMake' => MigrateMakeCommand::class,
        'MigrateInstall' => MigrateInstallCommand::class,
        'MigrateRefresh' => MigrateRefreshCommand::class,
        'MigrateReset' => MigrateResetCommand::class,
    ];

    public function register(): void
    {
        $this->registerRepository();

        $this->registerCreator();

        $this->registerMigrator();

        $this->registerCommands($this->commands);
    }

    protected function registerRepository(): void
    {
        $this->app->singleton('migration.repository', function ($app) {
            $table = $app['config']['database.migrations'];

            return new DatabaseMigrationRepository($app['db'], $table);
        });
    }

    protected function registerMigrator(): void
    {
        $this->app->singleton('migrator', function ($app) {
            return new Migrator($app['migration.repository'], $app['db'], $app['files'], $app['events']);
        });
    }

    protected function registerCreator(): void
    {
        $this->app->singleton('migration.creator', function ($app) {
            return new MigrationCreator($app['files'], $app->basePath('stubs'));
        });
    }

    protected function registerCommands(array $commands): void
    {
        foreach ($commands as $commandName => $command) {
            $method = "register{$commandName}Command";

            if (method_exists($this, $method)) {
                $this->{$method}();
            }else{
                $this->app->singleton($command, function (ApplicationInterface $app) use ($command) {
                    return new $command($app['files']);
                });
            };
        }

        $this->commands(array_values($commands));
    }

    protected function registerMigrateResetCommand(): void
    {
        $this->app->singleton(MigrateResetCommand::class, function (ApplicationInterface $app) {
            return new MigrateResetCommand($app['migrator']);
        });
    }

    protected function registerMigrateMakeCommand(): void
    {
        $this->app->singleton(MigrateMakeCommand::class, function (ApplicationInterface $app) {
            $creator = $app['migration.creator'];
            $composer = $app['composer'];

            return new MigrateMakeCommand($creator, $composer);
        });
    }

    protected function registerMigrateCommand(): void
    {
        $this->app->singleton(MigrateCommand::class, function ($app) {
            return new MigrateCommand($app['migrator'], $app['events']);
        });
    }

    protected function registerMigrateInstallCommand(): void
    {
        $this->app->singleton(MigrateInstallCommand::class, function (ApplicationInterface $app) {
            return new MigrateInstallCommand($app['migration.repository']);
        });
    }

    public function provides(): array
    {
        return array_merge([
            'migrator', 'migration.repository', 'migration.creator',
        ], array_values($this->commands));
    }
}
