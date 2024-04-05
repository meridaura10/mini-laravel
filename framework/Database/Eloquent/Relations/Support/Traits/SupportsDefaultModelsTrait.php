<?php

namespace Framework\Kernel\Database\Eloquent\Relations\Support\Traits;

use Framework\Kernel\Database\Eloquent\Model;

trait SupportsDefaultModelsTrait
{
    protected \Closure|array|bool $withDefault = false;

    abstract protected function newRelatedInstanceFor(Model $parent): Model;

    protected function getDefaultFor(Model $parent): ?Model
    {
        if (! $this->withDefault) {
            return null;
        }

        $instance = $this->newRelatedInstanceFor($parent);

        dd($instance);
    }
}