<?php

namespace Framework\Kernel\Console\Commands\Database\Migrations;

use Framework\Kernel\Console\Commands\BaseCommand;
use Framework\Kernel\Console\View\Components\Info;
use Framework\Kernel\Database\Migrations\Migrator;
use Framework\Kernel\Events\Contracts\DispatcherInterface;
use MongoDB\Driver\Exception\Exception;

class MigrateCommand extends BaseCommand
{
    protected ?string $signature = 'migrate {--database= : The database connection to use}
                {--force : Force the operation to run when in production}
                {--path=* : The path(s) to the migrations files to be executed}
                {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                {--schema-path= : The path to a schema dump file}
                {--pretend : Dump the SQL queries that would be run}
                {--seed : Indicates if the seed task should be re-run}
                {--seeder= : The class name of the root seeder}
                {--step : Force the migrations to be run so they can be rolled back individually}';

    protected ?string $description = 'Run the database migrations';

    public function __construct(
        protected Migrator            $migrator,
        protected DispatcherInterface $dispatcher,
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->migrator->usingConnection($this->option('database'), function () {
            $this->prepareDatabase();
            $this->migrator->setOutput($this->output)
                ->run($this->getMigrationPaths(), [
                    'pretend' => $this->option('pretend'),
                    'step' => $this->option('step'),
                ]);
        });

        return 0;
    }

    protected function prepareDatabase(): void
    {
        try {
            if (!$this->repositoryExists()) {
                $this->view->info('Preparing database.');

                $this->view->task('Creating migration table', function () {
                    return $this->callSilent('migrate:install', array_filter([
                            '--database' => $this->option('database'),
                        ])) == 0;
                });

//                $this->newLine();
            }

//        if (! $this->migrator->hasRunAnyMigrations() && ! $this->option('pretend')) {
//            $this->loadSchemaState();
//        }
        } catch (\Exception $exception) {
            $this->view->error('error to start migration ' . $exception->getMessage());
        }
    }

    protected function getMigrationPaths(): array
    {
        if ($this->input->hasOption('path') && $this->option('path')) {
            return collect($this->option('path'))->map(function ($path) {
                return !$this->usingRealPath()
                    ? $this->app->basePath() . '/' . $path
                    : $path;
            })->all();
        }

        return array_merge(
            $this->migrator->paths(), [$this->getMigrationPath()]
        );
    }

    protected function usingRealPath(): bool
    {
        return $this->input->hasOption('realpath') && $this->option('realpath');
    }

    protected function repositoryExists(): bool
    {
        try {
            return $this->migrator->repositoryExists();
        } catch (\Throwable $throwable) {
            throw $throwable;
        }
    }
}