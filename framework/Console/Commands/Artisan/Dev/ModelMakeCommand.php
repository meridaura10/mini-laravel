<?php

namespace Framework\Kernel\Console\Commands\Artisan\Dev;

use Framework\Kernel\Console\GeneratorCommand;
use Framework\Kernel\Console\Input\InputOption;
use Framework\Kernel\Support\Str;


class ModelMakeCommand extends GeneratorCommand
{
    protected ?string $name = 'make:model';

    protected ?string $description = 'Create a new Eloquent model class';

    protected string $type = 'Model';

    public function handle(): int
    {
        if (parent::handle() && ! $this->option('force')) {
            return false;
        }

//        if ($this->option('factory')) {
//            $this->createFactory();
//        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }
//
//        if ($this->option('policy')) {
//            $this->createPolicy();
//        }

        return 0;
    }

    protected function createSeeder(): void
    {
        $seeder = Str::studly(class_basename($this->argument('name')));

        $this->call('make:seeder', [
            'name' => "{$seeder}Seeder",
        ]);
    }

    protected function createMigration(): void
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        $this->call('make:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
        ]);
    }

    protected function getOptions(): array
    {
        return [
            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the model'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model'],
            ['policy', null, InputOption::VALUE_NONE, 'Create a new policy for the model'],
            ['seed', 's', InputOption::VALUE_NONE, 'Create a new seeder for the model'],
        ];
    }

    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return is_dir(app_path('Models')) ? $rootNamespace.'\\Models' : $rootNamespace;
    }

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/model.stub');
    }
}