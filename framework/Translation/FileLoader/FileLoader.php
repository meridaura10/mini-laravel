<?php

namespace Framework\Kernel\Translation\FileLoader;

use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;
use Framework\Kernel\Translation\Contracts\FileLoaderInterface;
use RuntimeException;


class FileLoader implements FileLoaderInterface
{
    protected array $jsonPaths = [];

    protected array $hints = [];

    protected array $paths = [];

    public function __construct(
        protected FilesystemInterface $files,
        array|string $paths,
    )
    {
         $this->paths = (array) $paths;
    }

    public function load(string $locale, string $group, ?string $namespace = null): array
    {

        if ($group === '*' && $namespace === '*') {
            return $this->loadJsonPaths($locale);
        }

        if (is_null($namespace) || $namespace === '*') {
            return $this->loadPaths($this->paths, $locale, $group);
        }

        return $this->loadNamespaced($locale, $group, $namespace);
    }

    protected function loadNamespaced(string $locale,string $group,string $namespace): array
    {
        if (isset($this->hints[$namespace])) {
            $lines = $this->loadPaths([$this->hints[$namespace]], $locale, $group);

            return $this->loadNamespaceOverrides($lines, $locale, $group, $namespace);
        }

        return [];
    }

    protected function loadNamespaceOverrides(array $lines,string $locale,string $group,string $namespace): array
    {
        return collect($this->paths)
            ->reduce(function ($output, $path) use ($lines, $locale, $group, $namespace) {
                $file = "{$path}/vendor/{$namespace}/{$locale}/{$group}.php";

                if ($this->files->exists($file)) {
                    $lines = array_replace_recursive($lines, $this->files->getRequire($file));
                }

                return $lines;
            }, []);
    }

    protected function loadPaths(array $paths,string $locale,string $group): array
    {
        return collect($paths)
            ->reduce(function ($output, $path) use ($locale, $group) {
                $full = "{$path}/{$locale}/{$group}.php";
                if ($this->files->exists($full = "{$path}/{$locale}/{$group}.php")) {
                    $output = array_replace_recursive($output, $this->files->getRequire($full));
                }

                return $output;
            }, []);
    }

    protected function loadJsonPaths(string $locale): array
    {
        return collect(array_merge($this->jsonPaths, $this->paths))
            ->reduce(function ($output, $path) use ($locale) {
                if ($this->files->exists($full = "{$path}/{$locale}.json")) {
                    $decoded = json_decode($this->files->get($full), true);

                    if (is_null($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException("Translation file [{$full}] contains an invalid JSON structure.");
                    }

                    $output = array_merge($output, $decoded);
                }

                return $output;
            }, []);
    }
}