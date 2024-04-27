<?php

namespace Framework\Kernel\Container\Contracts;

use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;

interface ContainerInterface
{
    public function bind(string $abstraction, callable $concrete, bool $shared): void;

    public function singleton(string $abstraction, callable|string $concrete): void;

    public function resolve(string $abstraction, array $parameters = []): mixed;

    public function make(string $abstraction): mixed;

    public function instance(string $abstraction, mixed $instance): mixed;

    public static function setInstance(ApplicationInterface $container): ApplicationInterface;

    public static function getInstance(): ApplicationInterface;

    public function getAlias(string $abstract): string;

    public function alias(string $abstraction, string $alias): void;

    public function afterResolving(Closure|string $abstract, Closure $callback = null): void;

    public function bound(string $abstract): bool;
}
