<?php

namespace Framework\Kernel\Database\Eloquent\Factories\Traits;

use Framework\Kernel\Database\Factories\Factory;

trait HasFactoryTrait
{
    public static function factory(int|array|callable|null $count = 1, callable|array $state = []): Factory
    {
        $factory = static::newFactory() ?: Factory::factoryForModel(get_called_class());

        return $factory
            ->count(is_numeric($count) ? $count : null)
            ->state(is_callable($count) || is_array($count) ? $count : $state);
    }

    protected static function newFactory(): ?Factory
    {
        return null;
    }
}