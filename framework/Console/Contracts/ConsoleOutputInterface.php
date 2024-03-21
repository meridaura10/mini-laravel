<?php

namespace Framework\Kernel\Console\Contracts;

use Symfony\Component\Console\Output\OutputInterface;

interface ConsoleOutputInterface extends OutputInterface
{
    public const VERBOSITY_QUIET = 16;

    public const VERBOSITY_NORMAL = 32;

    public const VERBOSITY_VERBOSE = 64;

    public const VERBOSITY_VERY_VERBOSE = 128;

    public const VERBOSITY_DEBUG = 256;

    public const OUTPUT_NORMAL = 1;

    public const OUTPUT_RAW = 2;

    public const OUTPUT_PLAIN = 4;
}
