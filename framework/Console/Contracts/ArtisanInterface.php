<?php

namespace Framework\Kernel\Console\Contracts;

interface ArtisanInterface
{
    public function run(InputInterface $input, ConsoleOutputInterface $output): int;
}
