<?php

namespace Framework\Kernel\Console\Event;

use Framework\Kernel\Console\Commands\Command;
use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;
use Framework\Kernel\Console\Contracts\InputInterface;

readonly class ConsoleCommandEvent
{
    public function __construct(
        public Command $command,
        public InputInterface $input,
        public ConsoleOutputInterface $output,
    ) {

    }
}
