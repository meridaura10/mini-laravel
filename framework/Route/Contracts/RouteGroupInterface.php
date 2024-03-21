<?php

namespace Framework\Kernel\Route\Contracts;

interface RouteGroupInterface
{
    public function prefix(string $prefix): static;

    public function controller(string $controller): static;

    public function middleware(string|array $middlewares): static;

    public function name(string $name): static;

    public function group(string|callable $group): void;
}
