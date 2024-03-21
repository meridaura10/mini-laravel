<?php

namespace Framework\Kernel\Foundation\Providers;

use Framework\Kernel\Foundation\Providers\Contracts\DefaultProvidersInterface;
use Framework\Kernel\Foundation\Providers\Services\ConsoleSupportServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\DatabaseServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\FilesystemProvider;
use Framework\Kernel\Foundation\Providers\Services\SessionServiceServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\ViewServiceProvider;

class DefaultProviders implements DefaultProvidersInterface
{
    protected array $providers = [];

    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ? $providers :
            [
                SessionServiceServiceProvider::class,
                FoundationServiceProvider::class,
                ViewServiceProvider::class,
                FilesystemProvider::class,
                DatabaseServiceProvider::class,
                ConsoleSupportServiceProvider::class,
            ];
    }

    public function merge(array $providers): static
    {
        $this->providers = array_merge($this->providers, $providers);

        return new static($this->providers);
    }

    public function toArray(): array
    {
        return $this->providers;
    }
}
