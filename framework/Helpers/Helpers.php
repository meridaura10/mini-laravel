<?php

use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;
use Framework\Kernel\Console\Termwind\HtmlRenderer;
use Framework\Kernel\Console\Termwind\Terminal;
use Framework\Kernel\Console\Termwind\Termwind;
use Framework\Kernel\Container\Container;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Collection;
use Framework\Kernel\Support\HigherOrderTapProxy;
use Framework\Kernel\Support\Str;
use Framework\Kernel\View\Contracts\ViewInterface;

if (! function_exists('collect')) {
    function collect(mixed $items = []): Collection
    {
        return new Collection($items);
    }
}

if (! function_exists('app')) {
    function app($abstract = null): mixed
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract);
    }
}


if (! function_exists('head')) {
    function head(array $array): array
    {
        return reset($array);
    }
}


if (! function_exists('last')) {
    function last(array $array): mixed
    {
        return end($array);
    }
}

if (! function_exists('app_path')) {

    function app_path(string $path = ''): string
    {
        return app()->path($path);
    }
}

if (! function_exists('tap')) {

    function tap(mixed $value, ?callable $callback = null)
    {
        if (is_null($callback)) {
            return new HigherOrderTapProxy($value);
        }

        $callback($value);

        return $value;
    }
}

if (! function_exists('with')) {

    function with(mixed $value, ?callable $callback = null): mixed
    {
        return is_null($callback) ? $value : $callback($value);
    }
}

if (! function_exists('view')) {

    function view(?string $path = null, array $data = [], array $mergeData = []): ViewInterface
    {
        $factory = app('view');

        if (! func_num_args()) {
            return $factory;
        }

        return $factory->make($path);
    }
}

if (! function_exists('env')) {

    function env(string $key, mixed $default = null): mixed
    {
        return $default;
        //        return Env::get($key, $default);
    }
}

if (! function_exists('class_basename')) {

    function class_basename(mixed $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (! function_exists('str')) {

    function str(?string $string = null): mixed
    {
        if (func_num_args() === 0) {
            return new class
            {
                public function __call($method, $parameters)
                {
                    return Str::$method(...$parameters);
                }

                public function __toString()
                {
                    return '';
                }
            };
        }

        return Str::of($string);
    }
}

if (! function_exists('value')) {

    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return app()->basePath($path);
    }
}

if (! function_exists('resource_path')) {

    function resource_path(string $path = '')
    {
        return app()->resourcePath($path);
    }
}

if (! function_exists('data_get')) {

    function data_get(mixed $target, string|array|int|null $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (! is_iterable($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}