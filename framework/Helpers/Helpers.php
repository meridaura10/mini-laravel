<?php

use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;
use Framework\Kernel\Console\Termwind\HtmlRenderer;
use Framework\Kernel\Console\Termwind\Terminal;
use Framework\Kernel\Console\Termwind\Termwind;
use Framework\Kernel\Container\Container;
use Framework\Kernel\Contracts\Support\Htmlable;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Collection;
use Framework\Kernel\Support\HigherOrderTapProxy;
use Framework\Kernel\Support\Str;
use Framework\Kernel\View\Contracts\DeferringDisplayableValueInterface;
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

if (! function_exists('fake')) {
    function fake(?string $locale = null): \Faker\Generator
    {
        if (app()->bound('config')) {
            $locale ??= app('config')->get('app.faker_locale');
        }

        $locale ??= 'en_US';

        $abstract = \Faker\Generator::class.':'.$locale;

        if (! app()->bound($abstract)) {
            app()->singleton($abstract, fn () => \Faker\Factory::create($locale));
        }

        return app()->make($abstract);
    }
}


if (! function_exists('now')) {
    function now(DateTimeZone|string|null $tz = null): \Carbon\Carbon
    {
        return \Carbon\Carbon::now($tz);
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

    function view(?string $path = null, array $data = [], array $mergeData = []): ViewInterface|\Framework\Kernel\View\Contracts\ViewFactoryInterface
    {
        $factory = app('view');

        if (! func_num_args()) {
            return $factory;
        }

        return $factory->make($path,$data);
    }
}

if (! function_exists('class_uses_recursive')) {
    function class_uses_recursive(object|string $class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_reverse(class_parents($class) ?: []) + [$class => $class] as $class) {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (! function_exists('trait_uses_recursive')) {
    function trait_uses_recursive(object|string $trait): array
    {
        $traits = class_uses($trait) ?: [];

        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}

if (! function_exists('env')) {

    function env(string $key, mixed $default = null): mixed
    {
        return $default;
        //        return Env::get($key, $default);
    }
}

if (! function_exists('e')) {
    function e(BackedEnum|DeferringDisplayableValueInterface|Htmlable|null|string $value,bool $doubleEncode = true): string
    {
        if ($value instanceof DeferringDisplayableValueInterface) {
            $value = $value->resolveDisplayableValue();
        }

        if ($value instanceof Htmlable) {
            return $value->toHtml();
        }

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
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

if (! function_exists('response')) {
    function response(ViewInterface|string|array|null $content = '',int $status = 200, array $headers = []): \Framework\Kernel\Http\Responses\Factory\Contracts\ResponseFactoryInterface|\Framework\Kernel\Http\Responses\Contracts\ResponseInterface
    {
        $factory = app(\Framework\Kernel\Http\Responses\Factory\Contracts\ResponseFactoryInterface::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($content, $status, $headers);
    }
}

if (! function_exists('config')) {
    function config(array|string|null $key = null,mixed $default = null): mixed
    {
        if (is_null($key)) {
            return app('config');
        }

        if (is_array($key)) {
            return app('config')->set($key);
        }

        return app('config')->get($key, $default);
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