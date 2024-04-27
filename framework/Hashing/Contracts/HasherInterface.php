<?php

namespace Framework\Kernel\Hashing\Contracts;

interface HasherInterface
{
    public function check(string $value,string $hashedValue, array $options = []): bool;

    public function make(string $value, array $options = []): string;

    public function info(string $hashedValue): array;
}