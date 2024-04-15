<?php

namespace Framework\Kernel\View\Contracts;

interface CompilerInterface
{
    public function getCompiledPath(string $path): string;

    public function compile(string $path): void;

    public function isExpired(string $path): bool;
}