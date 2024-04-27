<?php

namespace Framework\Kernel\Filesystem;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;
use Framework\Kernel\Filesystem\Contracts\FilesystemManagerInterface;
use Framework\Kernel\Support\Arr;
use InvalidArgumentException;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\Flysystem\PathPrefixing\PathPrefixedAdapter;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;

class FilesystemManager implements FilesystemManagerInterface
{
    protected array $disks = [];

    public function __construct(
        protected ApplicationInterface $app,
    )
    {

    }

    public function disk(?string $name = null): FilesystemInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->disks[$name] = $this->get($name);
    }

    protected function get(string $name): FilesystemInterface
    {
        return $this->disks[$name] ?? $this->resolve($name);
    }

    protected function resolve(string $name, ?array $config = null): FilesystemInterface
    {
        $config ??= $this->getConfig($name);

        if (empty($config['driver'])) {
            throw new InvalidArgumentException("Disk [{$name}] does not have a configured driver.");
        }

        $name = $config['driver'];

        $driverMethod = 'create' . ucfirst($name) . 'Driver';

        if (!method_exists($this, $driverMethod)) {
            throw new InvalidArgumentException("Driver [{$name}] is not supported.");
        }

        return $this->{$driverMethod}($config);
    }

    public function createLocalDriver(array $config): FilesystemInterface
    {
        $visibility = PortableVisibilityConverter::fromArray(
            $config['permissions'] ?? [],
            $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE
        );

        $links = ($config['links'] ?? null) === 'skip'
            ? LocalFilesystemAdapter::SKIP_LINKS
            : LocalFilesystemAdapter::DISALLOW_LINKS;

        $adapter = new LocalFilesystemAdapter(
            $config['root'], $visibility, $config['lock'] ?? LOCK_EX, $links
        );


        return new FilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config);
    }

    protected function createFlysystem(LocalFilesystemAdapter $adapter, array $config): FilesystemOperator
    {
        if ($config['read-only'] ?? false === true) {
            $adapter = new ReadOnlyFilesystemAdapter($adapter);
        }

        if (!empty($config['prefix'])) {
            $adapter = new PathPrefixedAdapter($adapter, $config['prefix']);
        }

        return new Flysystem($adapter, Arr::only($config, [
            'directory_visibility',
            'disable_asserts',
            'retain_visibility',
            'temporary_url',
            'url',
            'visibility',
        ]));
    }

    public function getDefaultDriver(): string
    {
        return $this->app['config']['filesystems.default'];
    }

    protected function getConfig(string $name): array
    {
        return $this->app['config']["filesystems.disks.{$name}"] ?: [];
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->disk()->$method(...$parameters);
    }
}
