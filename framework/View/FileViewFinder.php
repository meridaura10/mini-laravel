<?php

namespace Framework\Kernel\View;

use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;
use Framework\Kernel\View\Contracts\FileViewFinderInterface;
use Framework\Kernel\View\Exceptions\InvalidArgumentException;

class FileViewFinder implements FileViewFinderInterface
{
    protected array $paths = [];

    protected array $views = [];

    protected array $hints = [];

    protected array $extensions = ['php'];

    public function __construct(
        protected FilesystemInterface $files,
        array $paths,
        ?array $extensions = null,
    ) {
        $this->paths = array_map([$this, 'resolvePath'], $paths);

        if (isset($extensions)) {
            $this->extensions = $extensions;
        }
    }

    protected function resolvePath(string $path = ''): string
    {
        return realpath($path) ?: $path;
    }

    public function find(string $name): string
    {
        if (isset($this->views[$name])) {
            return $this->views[$name];
        }

        if ($this->hasHintInformation($name = trim($name))) {
            return $this->views[$name] = $this->findNamespacedView($name);
        }

        return $this->views[$name] = $this->findInPaths($name, $this->paths);
    }

    protected function hasHintInformation(string $name): bool
    {
        return strpos($name, static::HINT_PATH_DELIMITER) > 0;
    }

    protected function findNamespacedView(string $name): string
    {
        [$namespace, $view] = $this->parseNamespaceSegments($name);

        return $this->findInPaths($view, $this->hints[$namespace]);
    }

    protected function parseNamespaceSegments($name): array
    {
        $segments = explode(static::HINT_PATH_DELIMITER, $name);

        if (count($segments) !== 2) {
            throw new InvalidArgumentException("View [{$name}] has an invalid name.");
        }

        if (! isset($this->hints[$segments[0]])) {
            throw new InvalidArgumentException("No hint path defined for [{$segments[0]}].");
        }

        return $segments;
    }

    public function findInPaths(string $name, array $paths): string
    {
        foreach ($paths as $path) {
            foreach ($this->getPossibleViewFiles($name) as $file) {
                if ($this->files->exists($viewPath = $path.'/'.$file)) {
                    return $viewPath;
                }
            }
        }

        throw new InvalidArgumentException("View [{$name}] not found.");
    }

    protected function getPossibleViewFiles(string $name): array
    {
        return array_map(fn ($extension) => str_replace('.', '/', $name).'.'.$extension, $this->extensions);
    }
}
