<?php

namespace Framework\Kernel\Database\Eloquent\Relations;

use Framework\Kernel\Database\Eloquent\EloquentCollection;
use Framework\Kernel\Support\Collection;

class HasMany extends HasOneOrMany
{
    public function match(array $models, EloquentCollection $results, string $relation): array
    {
        return $this->matchMany($models, $results, $relation);
    }

    public function initRelation(array $models, string $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }
}