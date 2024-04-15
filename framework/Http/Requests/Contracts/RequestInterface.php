<?php

namespace Framework\Kernel\Http\Requests\Contracts;

use Framework\Kernel\Route\Route;

interface RequestInterface
{
    public function uri(): string;

    public function method(): string;

    public static function createFromGlobals(): static;

    public function input(string $key, $default = null): mixed;

    public function getRequestFormat(?string $default = 'html'): ?string;

    public function getMimeType(string $format): ?string;

    public function route(?string $param = null, mixed $default = null): Route|null|string;
}
