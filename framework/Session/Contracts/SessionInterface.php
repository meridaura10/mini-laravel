<?php

namespace Framework\Kernel\Session\Contracts;

interface SessionInterface
{
    public function set(string $key, mixed $value): void;

    public function getFlash(string $key, mixed $default = null): mixed;

    public function get(string $key, mixed $default = null): mixed;

    public function remove(string $key): void;

    public function has(string $key): bool;
}
