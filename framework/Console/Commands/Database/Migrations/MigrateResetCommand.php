<?php

namespace Framework\Kernel\Console\Commands\Database\Migrations;

use Framework\Kernel\Console\Commands\BaseCommand;
use Framework\Kernel\Console\Commands\Command;
use Framework\Kernel\Console\Input\InputOption;
use Framework\Kernel\Database\Migrations\Migrator;

class MigrateResetCommand extends BaseCommand
{
    protected ?string $name = 'migrate:reset';

    protected ?string $description = 'Rollback all database migrations';

    public function __construct(
        protected Migrator $migrator,
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->migrator->usingConnection($this->option('database'), function () {
            if (!$this->migrator->repositoryExists()) {
                $this->view->error('Migration table not found.');
                return 1;
            }

            $this->migrator->setOutput($this->output)->reset(
                $this->getMigrationPaths(), $this->option('pretend')
            );
        });

        return 0;
    }

    protected function getOptions(): array
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
            ['path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to the migrations files to be executed'],
            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run'],
        ];
    }
}