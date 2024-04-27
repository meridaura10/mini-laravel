<?php

namespace Framework\Kernel\Support;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Config\Contracts\ConfigManagerInterface;
use InvalidArgumentException;

abstract class Manager
{
    protected array $drivers = [];

    protected array $customCreators = [];

    protected ConfigManagerInterface $config;

    public function __construct(
        protected ApplicationInterface $app,
    )
    {
        $this->config = $app->make('config');
    }

    abstract public function getDefaultDriver(): string;

    public function driver(?string $driver = null): mixed
    {
        $driver = $driver ?: $this->getDefaultDriver();

        if (is_null($driver)) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].', static::class
            ));
        }

        if (! isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    protected function createDriver(string $driver): mixed
    {
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        $method = 'create'.Str::studly($driver).'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Driver [$driver] not supported.");
    }

    protected function callCustomCreator(string $driver): mixed
    {
        return $this->customCreators[$driver]($this->app);
    }

    public function __call(string $method,array $parameters): mixed
    {
        return $this->driver()->$method(...$parameters);
    }
}