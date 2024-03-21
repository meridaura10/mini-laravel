<?php

namespace Framework\Kernel\Console;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Console\Commands\Command;
use Framework\Kernel\Console\Contracts\CommandLoaderInterface;
use Framework\Kernel\Console\Exceptions\CommandNotFoundException;

class CommandLoader implements CommandLoaderInterface
{
    public function __construct(
        protected ApplicationInterface $app,
        protected array $commandMap,
    ) {

    }

    public function get(string $name): Command
    {
        if (! $this->has($name)) {
            throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
        }

        return $this->app->make($this->commandMap[$name]);
    }

    public function has(string $name): bool
    {
        return $name && isset($this->commandMap[$name]);
    }

    public function getNames(): array
    {
        return array_keys($this->commandMap);
    }
}
