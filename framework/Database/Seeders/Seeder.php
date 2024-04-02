<?php

namespace Framework\Kernel\Database\Seeders;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Console\Commands\Command;

abstract class Seeder
{
    protected ?ApplicationInterface $container = null;

    protected ?Command $command = null;

    public function setContainer(ApplicationInterface $container): static
    {
        $this->container = $container;

        return $this;
    }

    public function setCommand(Command $command): static
    {
        $this->command = $command;

        return $this;
    }

    public function __invoke(array $parameters = []): mixed
    {
        $callback = fn () => isset($this->container)
            ? $this->container->call([$this, 'run'], $parameters)
            : $this->run(...$parameters);


        return $callback();
    }

    abstract public function run();
}