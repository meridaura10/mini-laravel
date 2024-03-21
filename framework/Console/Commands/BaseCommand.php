<?php

namespace Framework\Kernel\Console\Commands;

class BaseCommand extends Command
{
    protected function getMigrationPath(): string
    {
        return $this->app->databasePath().DIRECTORY_SEPARATOR.'migrations';
    }
}
