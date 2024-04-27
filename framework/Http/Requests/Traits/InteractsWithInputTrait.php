<?php

namespace Framework\Kernel\Http\Requests\Traits;

use Framework\Kernel\Http\Requests\Bags\InputBag;
use Framework\Kernel\Support\Arr;

trait InteractsWithInputTrait
{
    public function input(?string $key = null, mixed $default = null): mixed
    {
        return data_get(
            $this->getInputSource()->all() + $this->query->all(), $key, $default
        );
    }

    public function only(mixed $keys): array
    {
        $results = [];

        $input = $this->all();

        $placeholder = new \stdClass();

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            $value = data_get($input, $key, $placeholder);

            if ($value !== $placeholder) {
                Arr::set($results, $key, $value);
            }
        }

        return $results;
    }

    public function boolean(?string $key = null,bool $default = false): bool
    {
        return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
    }


    public function header(?string $key = null, array|null|string $default = null): array|null|string
    {
        return $this->retrieveItem('headers', $key, $default);
    }

    protected function retrieveItem(string $source, ?string $key, array|null|string $default): array|null|string
    {
        if (is_null($key)) {
            return $this->{$source}->all();
        }

        if ($this->{$source} instanceof InputBag) {
            return $this->{$source}->all()[$key] ?? $default;
        }

        return $this->{$source}->get($key, $default);
    }

    public function all(mixed $keys = null): array
    {
        $input = array_replace_recursive($this->input(), $this->allFiles());

        if (!$keys) {
            return $input;
        }

        $results = [];

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            Arr::set($results, $key, Arr::get($input, $key));
        }

        return $results;
    }

    public function allFiles(): array
    {
        return [];
    }
}