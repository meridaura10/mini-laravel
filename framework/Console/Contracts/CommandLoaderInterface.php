<?php

namespace Framework\Kernel\Console\Contracts;

use Framework\Kernel\Console\Commands\Command;

interface CommandLoaderInterface
{
    public function get(string $name): Command;

    public function has(string $name): bool;

    public function getNames(): array;
}
