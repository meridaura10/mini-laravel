<?php

namespace Framework\Kernel\Session;

use Framework\Kernel\Session\Contracts\SessionInterface;

class Session implements SessionInterface
{
    public function set(string $key, mixed $value): void
    {
        // TODO: Implement set() method.
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        // TODO: Implement getFlash() method.
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // TODO: Implement get() method.
    }

    public function remove(string $key): void
    {
        // TODO: Implement remove() method.
    }

    public function has(string $key): bool
    {
        // TODO: Implement has() method.
    }
}
