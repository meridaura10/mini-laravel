<?php

namespace Framework\Kernel\Console\Commands\Database\Migrations;

use Framework\Kernel\Console\Commands\BaseCommand;
use Framework\Kernel\Console\Support\TableGuesser;
use Framework\Kernel\Database\Migrations\MigrationCreator;
use Framework\Kernel\Support\Composer;
use Framework\Kernel\Support\Str;

class MigrateMakeCommand extends BaseCommand
{
    protected ?string $signature = 'make:migration {name : The name of the migration}
        {--create= : The table to be created}
        {--table= : The table to migrate}
        {--path= : The location where the migration file should be created}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
        {--fullpath : Output the full path of the migration (Deprecated)}';

    protected ?string $description = 'Create a new migration file';

    public function __construct(
        protected MigrationCreator $creator,
        protected Composer $composer
    ) {
        parent::__construct();
    }

    public function hadnle(): int
    {
        $name = Str::snake(trim($this->argument('name')));

        $table = $this->option('table');

        $create = $this->option('create') ?: false;

        if (! $table && is_string($create)) {
            $table = $create;

            $create = true;
        }

        if (! $table) {
            [$table, $create] = TableGuesser::guess($name);
        }

        $this->writeMigration($name, $table,(bool) $create);

        return 0;
    }

    protected function writeMigration(string $name, ?string $table,bool $create): void
    {
        $file = $this->creator->create(
            $name, $this->getMigrationPath(), $table, $create
        );

        $this->view->info(sprintf('Migration [%s] created successfully.', $file));
    }
}