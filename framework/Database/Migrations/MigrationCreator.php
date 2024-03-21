<?php

namespace Framework\Kernel\Database\Migrations;

use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;
use Framework\Kernel\Support\Str;

class MigrationCreator
{
    public function __construct(
        protected FilesystemInterface $files,
        protected ?string $customStubPath = null,
    ) {

    }
    public function create(string $name, string $path,?string $table = null,bool $create = false): string
    {
//        $this->ensureMigrationDoesntAlreadyExist($name,$path);

        $stub = $this->getStub($table,$create);

        $path = $this->getPath($name, $path);

        $this->files->ensureDirectoryExists(dirname($path));

        $this->files->put(
            $path,$this->populateStub($stub, $table),
        );

        return $path;
    }

    protected function ensureMigrationDoesntAlreadyExist(string $name,?string $migrationPath = null): void
    {
        if (! empty($migrationPath)) {
            $migrationFiles = $this->files->glob($migrationPath.'/*.php');

            foreach ($migrationFiles as $migrationFile) {
                $this->files->requireOnce($migrationFile);
            }
        }

        if (class_exists($className = $this->getClassName($name))) {
            throw new \InvalidArgumentException("A {$className} class already exists.");
        }
    }

    protected function populateStub(string $stub, ?string $table): string
    {
        if (! is_null($table)) {
            $stub = str_replace(
                ['DummyTable', '{{ table }}', '{{table}}'],
                $table, $stub
            );
        }

        return $stub;
    }

    protected function getPath(string $name, string $path): string
    {
        return $path.'/'.$this->getDatePrefix().'_'.$name.'.php';
    }

    protected function getStub(?string $table,bool $create): string
    {
        if (is_null($table)) {
            $stub = $this->files->exists($customPath = $this->customStubPath.'/migration.stub')
                ? $customPath
                : $this->stubPath().'/migration.stub';
        } elseif ($create) {
            $stub = $this->files->exists($customPath = $this->customStubPath.'/migration.create.stub')
                ? $customPath
                : $this->stubPath().'/migration.create.stub';
        } else {
            $stub = $this->files->exists($customPath = $this->customStubPath.'/migration.update.stub')
                ? $customPath
                : $this->stubPath().'/migration.update.stub';
        }

        return $this->files->get($stub);
    }

    protected function getClassName(string $name): string
    {
        return Str::studly($name);
    }

    protected function getDatePrefix(): string
    {
        return date('Y_m_d_His');
    }

    public function stubPath(): string
    {
        return __DIR__.'/stubs';
    }
}