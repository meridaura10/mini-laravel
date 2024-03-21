<?php

namespace Framework\Kernel\Console\Traits;

use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;
use Framework\Kernel\Console\Input\ArrayInput;
use Framework\Kernel\Console\Output\NullOutput;
use Framework\Kernel\Console\SCommand;

trait CallsCommandsTrait
{
    protected function runCommand(string|SCommand $command, array $arguments, ConsoleOutputInterface $output): int
    {
        $arguments['command'] = $command;

        $result = $this->resolveCommand($command)->run(
            $this->createInputFromArguments($arguments), $output
        );
    }

    protected function createInputFromArguments(array $arguments): ArrayInput
    {
        return tap(new ArrayInput(array_merge($this->context(), $arguments)), function (ArrayInput $input) {
            if ($input->getParameterOption('--no-interaction')) {
                $input->setInteractive(false);
            }
        });
    }


    public function callSilent(SCommand|string $command, array $arguments = []): int
    {
        return $this->runCommand($command, $arguments, new NullOutput());
    }

    protected function context(): array
    {
        return collect($this->option())->only([
            'ansi',
            'no-ansi',
            'no-interaction',
            'quiet',
            'verbose',
        ])->filter()->mapWithKeys(function ($value, $key) {
            return ["--{$key}" => $value];
        })->all();
    }


    abstract protected function resolveCommand(SCommand|string $command): SCommand;
}
