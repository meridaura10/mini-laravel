<?php

namespace Framework\Kernel\Translation\Contracts;

interface FileLoaderInterface
{
    public function load(string $locale,string $group,?string $namespace = null): array;
}