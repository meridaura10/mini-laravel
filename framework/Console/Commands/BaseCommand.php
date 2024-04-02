<?php

namespace Framework\Kernel\Console\Commands;

class BaseCommand extends Command
{
    protected function getMigrationPaths(): array
    {
        if ($this->input->hasOption('path') && $this->option('path')) {
            return collect($this->option('path'))->map(function ($path) {
                return ! $this->usingRealPath()
                    ? $this->app->basePath().'/'.$path
                    : $path;
            })->all();
        }

        return array_merge(
            $this->migrator->paths(), [$this->getMigrationPath()]
        );
    }

    protected function getMigrationPath(): string
    {
        return $this->app->databasePath().DIRECTORY_SEPARATOR.'migrations';
    }
}
