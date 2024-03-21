<?php

namespace Framework\Kernel\Database\Contracts;

use Framework\Kernel\Database\Eloquent\Model;

interface BuilderInterface
{
    public function setModel(Model $model): static;

    public static function with(array $relations): static;

    public function withCount(mixed $relations): static;
}
