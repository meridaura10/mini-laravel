<?php

namespace Framework\Kernel\View\Compilers;

use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;
use Framework\Kernel\Support\Str;
use Framework\Kernel\View\Contracts\CompilerInterface;

abstract class Compiler implements CompilerInterface
{
    public function __construct(
        protected FilesystemInterface $files,
        protected string $cachePath,
        protected string $basePath = '',
        protected bool $shouldCache = true,
        protected string $compiledExtension = 'php'
    ) {
        //
    }
    public function isExpired(string $path): bool
    {
        if (! $this->shouldCache) {
            return true;
        }

        $compiled = $this->getCompiledPath($path);

        if (! $this->files->exists($compiled)) {
            return true;
        }

        try {
            return $this->files->lastModified($path) >=
                $this->files->lastModified($compiled);
        } catch (\ErrorException $exception) {
            if (! $this->files->exists($compiled)) {
                return true;
            }

            throw $exception;
        }
    }

    public function getCompiledPath(string $path): string
    {
        return $this->cachePath.'/'.hash('xxh128', 'v2'.Str::after($path, $this->basePath)).'.'.$this->compiledExtension;
    }
    protected function ensureCompiledDirectoryExists(string $path): void
    {
        if (! $this->files->exists(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }
}