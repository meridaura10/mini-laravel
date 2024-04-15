<?php

namespace Framework\Kernel\View\Contracts;

interface FileViewFinderInterface
{
    const HINT_PATH_DELIMITER = '::';

    public function find(string $name): string;

    public function addNamespace(string $namespace, array|string $hints): void;
}
