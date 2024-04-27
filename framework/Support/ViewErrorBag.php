<?php

namespace Framework\Kernel\Support;

use Framework\Kernel\Validator\Bags\MessageBag;

class ViewErrorBag
{
    protected array $bags = [];

    public function hasBag(string $key = 'default'): bool
    {
        return isset($this->bags[$key]);
    }

    public function getBag(string $key): MessageBag
    {
        return Arr::get($this->bags, $key) ?: new MessageBag;
    }


    public function getBags(): array
    {
        return $this->bags;
    }

    public function put(string $key, MessageBag $bag): static
    {
        $this->bags[$key] = $bag;

        return $this;
    }

    public function any(): bool
    {
        return $this->count() > 0;
    }

    public function count(): int
    {
        return $this->getBag('default')->count();
    }

    public function __call(string $method,array $parameters): mixed
    {
        return $this->getBag('default')->$method(...$parameters);
    }

    public function __get(string $key): MessageBag
    {
        return $this->getBag($key);
    }

    public function __set(string $key,MessageBag $value): void
    {
        $this->put($key, $value);
    }

    public function __toString(): string
    {
        return (string) $this->getBag('default');
    }
}