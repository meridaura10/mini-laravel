<?php

namespace Framework\Kernel\Console\Output;

use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

class ConsoleOutput implements ConsoleOutputInterface
{
    public function write(iterable|string $messages, bool $newline = false, int $options = 0): void
    {
        // TODO: Implement write() method.
    }

    public function writeln(iterable|string $messages, int $options = 0): void
    {
        // TODO: Implement writeln() method.
    }

    public function setVerbosity(int $level): void
    {
        // TODO: Implement setVerbosity() method.
    }

    public function getVerbosity(): int
    {
        // TODO: Implement getVerbosity() method.
    }

    public function isQuiet(): bool
    {
        // TODO: Implement isQuiet() method.
    }

    public function isVerbose(): bool
    {
        // TODO: Implement isVerbose() method.
    }

    public function isVeryVerbose(): bool
    {
        // TODO: Implement isVeryVerbose() method.
    }

    public function isDebug(): bool
    {
        // TODO: Implement isDebug() method.
    }

    public function setDecorated(bool $decorated): void
    {
        // TODO: Implement setDecorated() method.
    }

    public function isDecorated(): bool
    {
        // TODO: Implement isDecorated() method.
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        // TODO: Implement setFormatter() method.
    }

    public function getFormatter(): OutputFormatterInterface
    {
        // TODO: Implement getFormatter() method.
    }
}
