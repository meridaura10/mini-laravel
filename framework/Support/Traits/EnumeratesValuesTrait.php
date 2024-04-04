<?php

namespace Framework\Kernel\Support\Traits;

trait EnumeratesValuesTrait
{
    protected function useAsCallable(mixed $value): bool
    {
        return ! is_string($value) && is_callable($value);
    }

    public function flatMap(callable $callback): static
    {
        return $this->map($callback)->collapse();
    }

    protected function valueRetriever(callable|string|null $value): callable
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return fn ($item) => data_get($item, $value);
    }

    public function pipe(callable $callback): mixed
    {
        return $callback($this);
    }

    public function reduce(callable $callback,mixed $initial = null): mixed
    {
        $result = $initial;

        foreach ($this as $key => $value) {
            $result = $callback($result, $value, $key);
        }

        return $result;
    }
}