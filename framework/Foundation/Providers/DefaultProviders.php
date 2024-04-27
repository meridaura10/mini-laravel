<?php

namespace Framework\Kernel\Foundation\Providers;

use Framework\Kernel\Foundation\Providers\Contracts\DefaultProvidersInterface;
use Framework\Kernel\Foundation\Providers\Services\AuthServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\ConsoleSupportServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\CookieServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\DatabaseServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\ExceptionViewServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\FilesystemProvider;
use Framework\Kernel\Foundation\Providers\Services\HashServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\PaginationServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\SessionServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\TranslationServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\ValidationServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\ViewServiceProvider;

class DefaultProviders implements DefaultProvidersInterface
{
    protected array $providers = [];

    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ? $providers :
            [
                AuthServiceProvider::class,
                SessionServiceProvider::class,
                FoundationServiceProvider::class,
                ViewServiceProvider::class,
                HashServiceProvider::class,
                FilesystemProvider::class,
                DatabaseServiceProvider::class,
                ConsoleSupportServiceProvider::class,
                ValidationServiceProvider::class,
                TranslationServiceProvider::class,
                ExceptionViewServiceProvider::class,
                CookieServiceProvider::class,
                PaginationServiceProvider::class,
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
