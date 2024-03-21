<?php

namespace Framework\Kernel\Console\View\Components;

use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;

class Info extends Component
{
    public function render(string $content, $verbosity = ConsoleOutputInterface::VERBOSITY_NORMAL): void
    {
        with(new Line($this->output))->show('info', $content, $verbosity);
    }
}
