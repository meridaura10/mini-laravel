<?php

namespace Framework\Kernel\Http\Cookie\Contracts;

use Framework\Kernel\Http\Responses\Headers\Cookie;

interface CookieFactoryInterface
{
    public function getQueuedCookies(): array;

    public function forget(string $name,?string $path = null,?string $domain = null): Cookie;

    public function make(string $name,string $value,int $minutes = 0,?string $path = null,?string $domain = null,?string $secure = null,bool $httpOnly = true,bool $raw = false,?string $sameSite = null): Cookie;
}