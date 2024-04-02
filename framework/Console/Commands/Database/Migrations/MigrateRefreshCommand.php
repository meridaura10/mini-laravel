<?php

namespace Framework\Kernel\Console\Commands\Database\Migrations;

use Framework\Kernel\Console\Commands\Command;
use Framework\Kernel\Console\Input\InputOption;

class MigrateRefreshCommand extends Command
{
    protected ?string $name = 'migrate:refresh';

    protected ?string $description = 'Reset and re-run all migrations';

    public function handle(): int
    {
        $database = $this->option('database');

        $path = $this->option('path');

        $step = $this->option('step') ?: 0;

        if ($step > 0) {
            $this->runRollback($database, $path, $step);
        } else {
            $this->runReset($database, $path);
        }

        $this->call('migrate', array_filter([
            '--database' => $database,
            '--path' => $path,
            '--realpath' => $this->option('realpath'),
            '--force' => true,
            '--seeder' => $this->option('seeder'),
            '--seed' => $this->option('seed'),
        ]));

        return 0;
    }

    public function runReset(?string $database = null, ?array $path = null): void
    {
        $this->call('migrate:reset', array_filter([
            '--database' => $database,
            '--path' => $path,
            '--realpath' => $this->input->getOption('realpath'),
            '--force' => true,
        ]));
    }

    public function runRollback(): void
    {
        dd('run runRollback 50 line or migrate refresh');
    }

    protected function getOptions(): array
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
            ['path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to the migrations files to be executed'],
            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run'],
            ['seeder', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder'],
            ['step', null, InputOption::VALUE_OPTIONAL, 'The number of migrations to be reverted & re-run'],
        ];
    }
}