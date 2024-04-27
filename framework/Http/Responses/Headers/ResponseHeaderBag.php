<?php

namespace Framework\Kernel\Http\Responses\Headers;

use Framework\Kernel\Http\HeaderBag;

class ResponseHeaderBag extends HeaderBag
{
    public const COOKIES_FLAT = 'flat';

    public const COOKIES_ARRAY = 'array';

    public const DISPOSITION_ATTACHMENT = 'attachment';

    public const DISPOSITION_INLINE = 'inline';

    protected array $cookies = [];

    protected array $headerNames = [];

    public function __construct(array $headers = [])
    {
        parent::__construct($headers);

        if (! isset($this->headers['cache-control'])) {
            $this->set('Cache-Control', '');
        }

        if (! isset($this->headers['date'])) {
            $this->initDate();
        }
    }

    public function all(?string $key = null): array
    {
        $headers = parent::all();

        if (! $key) {
            foreach ($this->getCookies() as $cookie) {
                $headers['set-cookie'][] = (string) $cookie;
            }

            return $headers;
        }

        $key = strtr($key, self::UPPER, self::LOWER);

        return $key !== 'set-cookie' ? $headers[$key] ?? [] : array_map('strval', $this->getCookies());
    }

    public function allPreserveCaseWithoutCookies(): array
    {
        $headers = $this->allPreserveCase();
        if (isset($this->headerNames['set-cookie'])) {
            unset($headers[$this->headerNames['set-cookie']]);
        }

        return $headers;
    }

    public function allPreserveCase(): array
    {
        $headers = [];
        foreach ($this->all() as $name => $value) {
            $headers[$this->headerNames[$name] ?? $name] = $value;
        }

        return $headers;
    }

    public function getCookies(string $format = self::COOKIES_FLAT): array
    {
        if (! in_array($format, [self::COOKIES_FLAT, self::COOKIES_ARRAY])) {
            throw new \InvalidArgumentException(sprintf('Format "%s" invalid (%s).', $format, implode(', ', [self::COOKIES_FLAT, self::COOKIES_ARRAY])));
        }

        if ($format === self::COOKIES_ARRAY) {
            return $this->cookies;
        }

        $flattenedCookies = [];
        foreach ($this->cookies as $path) {
            foreach ($path as $cookies) {
                foreach ($cookies as $cookie) {
                    $flattenedCookies[] = $cookie;
                }
            }
        }

        return $flattenedCookies;
    }

    public function setCookie(Cookie $cookie): void
    {
        $this->cookies[$cookie->getDomain()][$cookie->getPath()][$cookie->getName()] = $cookie;
        $this->headerNames['set-cookie'] = 'Set-Cookie';
    }

    private function initDate(): void
    {
        $this->set('Date', gmdate('D, d M Y H:i:s').' GMT');
    }
}
