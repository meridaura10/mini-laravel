<?php

namespace Framework\Kernel\Session;

use Carbon\Carbon;
use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;
use SessionHandlerInterface;

class FileSessionHandler implements SessionHandlerInterface
{
    public function __construct(
        protected FilesystemInterface $files,
        protected string $path,
        protected int $minutes
    ) {

    }

    public function close(): bool
    {
        // TODO: Implement close() method.
    }

    public function destroy(string $id): bool
    {
        $this->files->delete($this->path.'/'.$id);

        return true;
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
        if ($this->files->isFile($path = $this->path.'/'.$id) &&
            $this->files->lastModified($path) >= Carbon::now()->subMinutes($this->minutes)->getTimestamp()) {
            return $this->files->sharedGet($path);
        }

        return '';
    }

    public function write(string $id,string $data): bool
    {
        $this->files->put($this->path.'/'.$id, $data, true);

        return true;
    }
}