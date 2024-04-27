<?php

namespace Framework\Kernel\Session\Contracts;

use Framework\Kernel\Http\Requests\Contracts\RequestInterface;

interface SessionStoreInterface
{
    public function get(string $key,mixed $default = null): mixed;

    public function invalidate(): bool;

    public function flush(): void;

    public function start(): void;

    public function remove(string $key): mixed;

    public function pull(string $key,mixed $default = null): mixed;

    public function has(array|string $key): bool;

    public function setId(?string $id): void;

    public function put(array|string $key, mixed $value): void;

    public function getOldInput(?string $key = null,mixed $default = null): mixed;

    public function getName(): string;

    public function save(): void;

    public function regenerate($destroy = false): bool;

    public function setRequestOnHandler(RequestInterface $request): void;

    public function migrate(bool $destroy = false): bool;

    public function flashInput(array $value): void;

    public function push(string $key,mixed $value): void;

    public function all(): array;

    public function setPreviousUrl(string $url): void;
}