<?php

namespace Framework\Kernel\Http\Requests\Bags;

use Framework\Kernel\Http\Requests\Exception\BadRequestException;

abstract class AbstractRequestBag
{
    public function __construct(protected array $parameters = [])
    {

    }

    public function all(?string $key = null): array
    {
        if (! $key) {
            return $this->parameters;
        }

        if (! \is_array($value = $this->parameters[$key] ?? [])) {
            throw new BadRequestException(sprintf('Unexpected value for parameter "%s": expecting "array", got "%s".', $key, get_debug_type($value)));
        }

        return $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : $default;
    }


    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }
}
