<?php

namespace Framework\Kernel\Console\Commands\Artisan\Dev;

use Framework\Kernel\Console\GeneratorCommand;
use Framework\Kernel\Console\Input\InputOption;


class RequestMakeCommand extends GeneratorCommand
{
    protected ?string $name = 'make:request';

    protected ?string $description = 'Create a new form request class';

    protected string $type = 'Request';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/request.stub');
    }

    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace.'\Http\Requests';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the request already exists'],
        ];
    }
}