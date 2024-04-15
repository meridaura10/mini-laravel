<?php

namespace Framework\Kernel\Route\Contracts;

use Framework\Kernel\Database\Eloquent\Model;

interface UrlRoutableInterface
{
    public function resolveRouteBinding(mixed $value,?string $field = null): ?Model;

    public function getRouteKeyName(): string;

}