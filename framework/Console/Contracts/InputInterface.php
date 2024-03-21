<?php

namespace Framework\Kernel\Console\Contracts;

interface InputInterface
{
    public function getFirstArgument(): ?string;

    public function getArgument(string|int $name): mixed;

    public function getArguments(): array;

    public function getOption(string $name): mixed;

    public function hasOption(string $name): bool;

    public function getOptions(): array;
}
