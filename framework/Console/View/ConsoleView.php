<?php

namespace Framework\Kernel\Console\View;

use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;
use Framework\Kernel\Console\View\Components\Error;
use Framework\Kernel\Console\View\Components\Info;
use Framework\Kernel\Console\View\Components\Task;
use Framework\Kernel\Console\View\Contracts\ConsoleViewInterface;

class ConsoleView implements ConsoleViewInterface
{
    public function __construct(
        protected ConsoleOutputInterface $output,
    ) {

    }

    public function start(array $params, string $component): void
    {
        $component = new $component($this->output);

        $component->render(...$params);
    }

    public function info(string $content): void
    {
        $this->start([$content], Info::class);
    }

    public function error(string $content): void
    {
        $this->start([$content], Error::class);
    }

    public function task(string $description, ?callable $task = null): void
    {
        $this->start([$description, $task], Task::class);
    }
}
