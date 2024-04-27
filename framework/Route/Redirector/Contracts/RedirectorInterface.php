<?php

namespace Framework\Kernel\Route\Redirector\Contracts;

use Framework\Kernel\Http\Responses\RedirectResponse;
use Framework\Kernel\Route\UrlGenerator\UrlGeneratorInterface;
use Framework\Kernel\Session\Contracts\SessionStoreInterface;

interface RedirectorInterface
{
    public function setSession(SessionStoreInterface $session): void;

    public function to(string $path,int $status = 302,array $headers = [],?bool $secure = null): RedirectResponse;

    public function getUrlGenerator(): UrlGeneratorInterface;

    public function back(int $status = 302,array $headers = [],bool $fallback = false): RedirectResponse;

    public function intended(mixed $default = '/',int $status = 302,array $headers = [],?bool $secure = null): RedirectResponse;

    public function route(string $route,array $parameters = [],int $status = 302,array $headers = []): RedirectResponse;
}