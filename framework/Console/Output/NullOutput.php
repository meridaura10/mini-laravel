<?php

namespace Framework\Kernel\Console\Output;

use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

class NullOutput implements ConsoleOutputInterface
{

    /**
     * @inheritDoc
     */
    public function write(iterable|string $messages, bool $newline = false, int $options = 0): void
    {
        // TODO: Implement write() method.
    }

    /**
     * @inheritDoc
     */
    public function writeln(iterable|string $messages, int $options = 0): void
    {
        // TODO: Implement writeln() method.
    }

    /**
     * @inheritDoc
     */
    public function setVerbosity(int $level): void
    {
        // TODO: Implement setVerbosity() method.
    }

    /**
     * @inheritDoc
     */
    public function getVerbosity(): int
    {
        // TODO: Implement getVerbosity() method.
    }

    /**
     * @inheritDoc
     */
    public function isQuiet(): bool
    {
        // TODO: Implement isQuiet() method.
    }

    /**
     * @inheritDoc
     */
    public function isVerbose(): bool
    {
        // TODO: Implement isVerbose() method.
    }

    /**
     * @inheritDoc
     */
    public function isVeryVerbose(): bool
    {
        // TODO: Implement isVeryVerbose() method.
    }

    /**
     * @inheritDoc
     */
    public function isDebug(): bool
    {
        // TODO: Implement isDebug() method.
    }

    /**
     * @inheritDoc
     */
    public function setDecorated(bool $decorated): void
    {
        // TODO: Implement setDecorated() method.
    }

    /**
     * @inheritDoc
     */
    public function isDecorated(): bool
    {
        // TODO: Implement isDecorated() method.
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        // TODO: Implement setFormatter() method.
    }

    /**
     * @inheritDoc
     */
    public function getFormatter(): OutputFormatterInterface
    {
        // TODO: Implement getFormatter() method.
    }
}