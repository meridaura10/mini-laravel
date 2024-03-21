<?php

namespace Framework\Kernel\Route\Contracts;

use Framework\Kernel\Http\Requests\Contracts\RequestInterface;

interface RouterInterface
{
    public function setMiddlewarePriority(array $middleware): void;

    public function setMiddlewareGroup(string $name, array $middleware): void;

    public function setAliasMiddleware(string $name, string $middleware): void;

    public function dispatch(RequestInterface $request): mixed;
}
