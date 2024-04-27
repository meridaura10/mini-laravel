<?php

namespace Framework\Kernel\Route\UrlGenerator;

use Framework\Kernel\Http\Requests\Contracts\RequestInterface;

interface UrlGeneratorInterface
{
    public function asset(string $path,?bool $secure = null): string;

    public function getRequest(): RequestInterface;

    public function previous(mixed $fallback = false): string;
}