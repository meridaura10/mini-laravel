<?php

namespace Framework\Kernel\Database\Traits;

use Framework\Kernel\Database\Eloquent\Model;

trait BuildsQueriesTrait
{
    public function first(array $columns = ['*']): ?Model
    {
        return $this->take(1)->get($columns)->first();
    }
}
