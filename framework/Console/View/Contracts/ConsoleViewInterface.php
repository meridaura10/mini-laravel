<?php

namespace Framework\Kernel\Console\View\Contracts;

interface ConsoleViewInterface
{
    public function info(string $content): void;

    public function error(string $content): void;

    public function task(string $description, ?callable $task = null): void;
}
