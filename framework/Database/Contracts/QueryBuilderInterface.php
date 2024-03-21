<?php

namespace Framework\Kernel\Database\Contracts;

interface QueryBuilderInterface
{
    public function getConnection(): ConnectionInterface;

    public function insertGetId(array $values, ?string $sequence = null): int;

    public function from(string $table): static;
}
