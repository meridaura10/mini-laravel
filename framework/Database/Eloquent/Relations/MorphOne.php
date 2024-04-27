<?php

namespace Framework\Kernel\Database\Eloquent\Relations;

use Framework\Kernel\Database\Eloquent\EloquentCollection;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Eloquent\Relations\MorphOneOrMany;
use Framework\Kernel\Database\Eloquent\Relations\Support\Traits\SupportsDefaultModelsTrait;

class MorphOne extends MorphOneOrMany
{
    use SupportsDefaultModelsTrait;
    public function match(array $models, EloquentCollection $results, string $relation): array
    {
        return $this->matchOne($models, $results, $relation);
    }

    public function initRelation(array $models, string $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    public function newRelatedInstanceFor(Model $parent): Model
    {
        return $this->related->newInstance()
            ->setAttribute($this->getForeignKeyName(), $parent->{$this->localKey})
            ->setAttribute($this->getMorphType(), $this->morphClass);
    }
}