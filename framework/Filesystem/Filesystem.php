<?php

namespace Framework\Kernel\Filesystem;

use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;
use Framework\Kernel\Filesystem\Exceptions\FileNotFoundException;

class Filesystem implements FilesystemInterface
{
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function getRequire($path, array $data = [])
    {
        if ($this->isFile($path));
        $__path = $path;
        $__data = $data;

        return (static function () use ($__path, $__data) {
            extract($__data, EXTR_SKIP);

            return require $__path;
        })();
    }

    public function isFile(string $file): bool
    {
        return is_file($file);
    }

    public function size(string $path): int
    {
        return filesize($path);
    }

    public function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false): bool
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    public function put(string $path, string $contents, bool $lock = false): int|bool
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    public function get(string $path, bool $lock = false): string
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    public function sharedGet(string $path): string
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $contents = fread($handle, $this->size($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }


    public function glob(string $pattern,int $flags = 0): array
    {
        return glob($pattern, $flags);
    }

    public function ensureDirectoryExists(string $path,int $mode = 0755,bool $recursive = true): void
    {
        if (! $this->isDirectory($path)) {
            $this->makeDirectory($path, $mode, $recursive);
        }
    }

    public function requireOnce(string $path, array $data = []): mixed
    {
        if ($this->isFile($path)) {
            $__path = $path;
            $__data = $data;

            return (static function () use ($__path, $__data) {
                extract($__data, EXTR_SKIP);

                return require_once $__path;
            })();
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }
}
