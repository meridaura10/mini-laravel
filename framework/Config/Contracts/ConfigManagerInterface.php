<?php

namespace Framework\Kernel\Config\Contracts;

interface ConfigManagerInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function has($key): bool;

    public function getMany(array $keys): array;

    public function set(string|array $key, mixed $value = null): void;
}
