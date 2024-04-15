<?php

namespace Framework\Kernel\Filesystem\Contracts;

interface FilesystemInterface
{
    public function exists(string $path): bool;

    public function getRequire($path, array $data = []);

    public function isFile(string $file): bool;

    public function isDirectory(string $path): bool;

    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false): bool;

    public function put(string $path, string $contents, bool $lock = false): int|bool;

    public function lastModified(string $path): false|int;

    public function get(string $path, bool $lock = false): string;

    public function ensureDirectoryExists(string $path,int $mode = 0755,bool $recursive = true): void;

    public function requireOnce(string $path, array $data = []): mixed;
}
