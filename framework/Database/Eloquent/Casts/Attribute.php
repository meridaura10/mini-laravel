<?php

namespace Framework\Kernel\Database\Eloquent\Casts;

class Attribute
{
    public function __construct(
        public ?\Closure $get = null,
        public ?\Closure $set = null
    ){

    }

    public static function get(callable $get): static
    {
        return new static($get);
    }

    public static function make(callable $get = null, callable $set = null): static
    {
        return new static($get, $set);
    }
}