<?php

namespace Framework\Kernel\Application\Contracts;

use Closure;
use Framework\Kernel\Container\Contracts\ContainerInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

interface ApplicationInterface extends ContainerInterface
{
    public function boot(): void;

    public function booted(callable $callback): void;

    public function resolveProvider(string $provider): ServiceProvider;

    public function register(ServiceProvider|string $provider): ServiceProvider;

    public function resolving(string $abstraction, ?Closure $callback = null): void;

    public function configPath(string $path = ''): string;

    public function loadDeferredProviders(): void;

    public function getNamespace(): string;

    public function getLocale(): string;

    public function getFallbackLocale(): string;

    public function basePath(string $path = ''): string;
}
