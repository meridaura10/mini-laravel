<?php

namespace Framework\Kernel\Http;

class HeaderBag
{
    protected const UPPER = '_ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    protected const LOWER = '-abcdefghijklmnopqrstuvwxyz';

    protected array $headers = [];

    protected array $cacheControl = [];

    public function __construct(array $headers = [])
    {
        foreach ($headers as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function set(string $key, string|array|null $values, bool $replace = true): void
    {
        $key = strtr($key, self::UPPER, self::LOWER);

        $values = is_array($values) ? $values : [$values];

        $this->headers[$key] = $replace || ! isset($this->headers[$key])
            ? $values
            : array_merge($this->headers[$key], $values);

        if ($key === 'cache-control') {
            $this->cacheControl = $this->parseCacheControl(implode(', ', $this->headers[$key]));
        }
    }

    public function parseCacheControl($header): array
    {
        $directives = array_map('trim', explode(',', $header));
        $values = [];

        foreach ($directives as $directive) {
            if (strpos($directive, '=') !== false) {
                [$key, $value] = explode('=', $directive, 2);
                $values[trim($key)] = trim($value);
            } else {
                $values[trim($directive)] = true;
            }
        }

        return $values;
    }

    public function remove(string $key): void
    {
        $key = strtr($key, self::UPPER, self::LOWER);

        unset($this->headers[$key]);

        if ($key === 'cache-control') {
            $this->cacheControl = [];
        }
    }

    public function has(string $key): bool
    {
        return \array_key_exists(strtr($key, self::UPPER, self::LOWER), $this->all());
    }

    public function all(?string $key = null): array
    {
        if (! $key) {
            return $this->headers;
        }

        return $this->headers[strtr($key, self::UPPER, self::LOWER)] ?? [];
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $headers = $this->all($key);

        if (! $headers) {
            return $default;
        }

        if (! isset($headers[0])) {
            return null;
        }

        return (string) $headers[0];
    }
}
