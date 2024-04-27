<?php

namespace Framework\Kernel\Session;

use SessionHandlerInterface;

class CookieSessionHandler implements SessionHandlerInterface
{

    public function close(): bool
    {
        // TODO: Implement close() method.
    }

    public function destroy(string $id): bool
    {
        // TODO: Implement destroy() method.
    }

    public function gc(int $max_lifetime): int|false
    {
        // TODO: Implement gc() method.
    }

    public function open(string $path, string $name): bool
    {
        // TODO: Implement open() method.
    }

    public function read(string $id): string|false
    {
        // TODO: Implement read() method.
    }

    public function write(string $id, string $data): bool
    {
        // TODO: Implement write() method.
    }
}