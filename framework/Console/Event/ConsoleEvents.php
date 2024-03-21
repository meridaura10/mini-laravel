<?php

namespace Framework\Kernel\Console\Event;

final class ConsoleEvents
{
    public const COMMAND = 'console.command';

    public const SIGNAL = 'console.signal';

    public const TERMINATE = 'console.terminate';

    public const ERROR = 'console.error';

    public const ALIASES = [
        ConsoleCommandEvent::class => self::COMMAND,
        //        ConsoleErrorEvent::class => self::ERROR,
        //        ConsoleSignalEvent::class => self::SIGNAL,
        //        ConsoleTerminateEvent::class => self::TERMINATE,
    ];
}
