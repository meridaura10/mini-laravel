<?php

namespace Framework\Kernel\Validator\Contracts;

interface PresenceVerifierInterface
{
    public function getCount(string $collection,string $column,string $value,?string $excludeId = null,?string $idColumn = null, array $extra = []): int;

    public function getMultiCount(string $collection,string $column, array $values, array $extra = []): int;
}