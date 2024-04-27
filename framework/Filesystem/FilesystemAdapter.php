<?php

namespace Framework\Kernel\Filesystem;

use AllowDynamicProperties;
use Framework\Kernel\Filesystem\Contracts\FilesystemCloudInterface;
use Framework\Kernel\Support\Str;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToDeleteFile;

class FilesystemAdapter implements FilesystemCloudInterface
{
    protected PathPrefixer $prefix;

    public function __construct(
        protected FilesystemOperator $driver,
        protected FlysystemAdapter $adapter,
        protected array $config = []
    ) {
        $separator = $config['directory_separator'] ?? DIRECTORY_SEPARATOR;

        $this->prefixer = new PathPrefixer($config['root'] ?? '', $separator);

        if (isset($config['prefix'])) {
            $this->prefixer = new PathPrefixer($this->prefixer->prefixPath($config['prefix']), $separator);
        }
    }

    public function files(?string $directory = null, bool $recursive = false): array
    {
        return $this->driver->listContents($directory ?? '', $recursive)
            ->filter(function (StorageAttributes $attributes) {
                return $attributes->isFile();
            })
            ->sortByPath()
            ->map(function (StorageAttributes $attributes) {
                return $attributes->path();
            })
            ->toArray();
    }

    public function url(string $path): string
    {
        if (isset($this->config['prefix'])) {
            $path = $this->concatPathToUrl($this->config['prefix'], $path);
        }

        $adapter = $this->adapter;

        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        } elseif (method_exists($this->driver, 'getUrl')) {
            return $this->driver->getUrl($path);
        } elseif ($adapter instanceof FtpAdapter || $adapter instanceof SftpAdapter) {
            return $this->getFtpUrl($path);
        } elseif ($adapter instanceof LocalAdapter) {
            return $this->getLocalUrl($path);
        } else {
            throw new \RuntimeException('This driver does not support retrieving URLs.');
        }
    }

    protected function getLocalUrl($path)
    {
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $path);
        }

        $path = '/storage/'.$path;

        if (str_contains($path, '/storage/public/')) {
            return Str::replaceFirst('/public/', '/', $path);
        }

        return $path;
    }

    protected function concatPathToUrl(string $url,string $path): string
    {
        return rtrim($url, '/').'/'.ltrim($path, '/');
    }


    public function exists(string $path): bool
    {
        // TODO: Implement exists() method.
    }

    public function getRequire($path, array $data = [])
    {
        // TODO: Implement getRequire() method.
    }

    public function isFile(string $file): bool
    {
        // TODO: Implement isFile() method.
    }

    public function isDirectory(string $path): bool
    {
        // TODO: Implement isDirectory() method.
    }

    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false): bool
    {
        // TODO: Implement makeDirectory() method.
    }

    public function put(string $path, string $contents, bool $lock = false): int|bool
    {
        // TODO: Implement put() method.
    }

    public function lastModified(string $path): false|int
    {
        // TODO: Implement lastModified() method.
    }

    public function get(string $path, bool $lock = false): string
    {
        // TODO: Implement get() method.
    }

    public function ensureDirectoryExists(string $path, int $mode = 0755, bool $recursive = true): void
    {
        // TODO: Implement ensureDirectoryExists() method.
    }

    public function requireOnce(string $path, array $data = []): mixed
    {
        // TODO: Implement requireOnce() method.
    }

    public function delete(array|string $paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                $this->driver->delete($path);
            } catch (UnableToDeleteFile $e) {
                throw_if($this->throwsExceptions(), $e);

                $success = false;
            }
        }

        return $success;
    }
}