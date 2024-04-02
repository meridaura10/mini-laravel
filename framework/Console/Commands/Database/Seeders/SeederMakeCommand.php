<?php

namespace Framework\Kernel\Console\Commands\Database\Seeders;

use Framework\Kernel\Console\GeneratorCommand;
use Framework\Kernel\Support\Str;

class SeederMakeCommand extends GeneratorCommand
{
    protected ?string $name = 'make:seeder';

    protected ?string $description = 'Create a new seeder class';

    protected string $type = 'seed';

    public function getStub(): string
    {
        return $this->resolveStubPath('/stubs/seeder.stub');
    }

    protected function getPath(string $name): string
    {
        $name = str_replace('\\', '/', Str::replaceFirst($this->rootNamespace(), '', $name));

        if (is_dir($this->app->databasePath().'/seeds')) {
            return $this->app->databasePath().'/seeds/'.$name.'.php';
        }

        return $this->app->databasePath().'/Seeders/'.$name.'.php';
    }

    protected function rootNamespace(): string
    {
        return 'Database\Seeders\\';
    }
}