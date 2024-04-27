<?php

namespace Framework\Kernel\Http\Cookie;

use Framework\Kernel\Http\Cookie\Contracts\QueueingFactoryInterface;
use Framework\Kernel\Http\Responses\Headers\Cookie;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Traits\InteractsWithTimeTrait;

class CookieJar implements QueueingFactoryInterface
{
    use InteractsWithTimeTrait;

    protected string $path = '/';

    protected array $queued = [];

    protected ?string $domain = null;

    protected ?bool $secure = null;

    protected string $sameSite = 'lax';


    public function setDefaultPathAndDomain(string $path, ?string $domain, ?bool $secure = false, ?string $sameSite = null): static
    {
        [$this->path, $this->domain, $this->secure, $this->sameSite] = [$path, $domain, $secure, $sameSite];

        return $this;
    }

    protected function getPathAndDomain(?string $path,?string $domain,?bool $secure = null,?string $sameSite = null): array
    {
        return [$path ?: $this->path, $domain ?: $this->domain, is_bool($secure) ? $secure : $this->secure, $sameSite ?: $this->sameSite];
    }

    public function queue(...$parameters): void
    {
        if (isset($parameters[0]) && $parameters[0] instanceof Cookie) {
            $cookie = $parameters[0];
        } else {
            $cookie = $this->make(...array_values($parameters));
        }

        if (! isset($this->queued[$cookie->getName()])) {
            $this->queued[$cookie->getName()] = [];
        }

        $this->queued[$cookie->getName()][$cookie->getPath()] = $cookie;
    }

    public function make(string $name, ?string $value, int $minutes = 0, ?string $path = null, ?string $domain = null, ?string $secure = null, bool $httpOnly = true, bool $raw = false, ?string $sameSite = null): Cookie
    {
        [$path, $domain, $secure, $sameSite] = $this->getPathAndDomain($path, $domain, $secure, $sameSite);

        $time = ($minutes == 0) ? 0 : $this->availableAt($minutes * 60);

        return new Cookie($name, $value, $time, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
    }

    public function getQueuedCookies(): array
    {
        return Arr::flatten($this->queued);
    }

    public function unqueue(string $name, ?string $path = null): void
    {
        if ($path === null) {
            unset($this->queued[$name]);

            return;
        }

        unset($this->queued[$name][$path]);

        if (empty($this->queued[$name])) {
            unset($this->queued[$name]);
        }
    }

    public function forget(string $name, ?string $path = null, ?string $domain = null): Cookie
    {
        return $this->make($name, null, -2628000, $path, $domain);
    }
}