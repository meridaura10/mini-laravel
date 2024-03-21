<?php

namespace Framework\Kernel\Database\Contracts;

use Closure;

interface SchemaBuilderInterface
{
    public function hasTable(string $name): bool;

    public function create(string $table, Closure $callback): void;
}