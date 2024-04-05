<?php

namespace Framework\Kernel\Database\Eloquent\Relations;

use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Eloquent\Relations\Support\Traits\SupportsDefaultModelsTrait;
use Framework\Kernel\Support\Collection;

class BelongsTo extends Relation
{
    use SupportsDefaultModelsTrait;

    public function __construct(
        BuilderInterface $query,
        protected Model $child,
        protected string $foreignKey,
        protected string $ownerKey,
        protected string $relationName)
    {
        parent::__construct($query, $child);
    }


    public function getOwnerKeyName(): string
    {
        return $this->ownerKey;
    }

    public function getForeignKeyName(): string
    {
        return $this->foreignKey;
    }

    public function addEagerConstraints(array $models): void
    {
        $key = $this->related->getTable().'.'.$this->ownerKey;

        $whereIn = $this->whereInMethod($this->related, $this->ownerKey);

        $this->whereInEager($whereIn, $key, $this->getEagerModelKeys($models));
    }

    public function getEagerModelKeys(array $models): array
    {
        $keys = [];

        foreach ($models as $model){
            if(! is_null($value = $this->getForeignKeyFrom($model))){
                $keys[] = $value;
            }
        }

        sort($keys);

        return array_values(array_unique($keys));
    }

    protected function getForeignKeyFrom(Model $model): mixed
    {
        return $model->{$this->foreignKey};
    }

    public function match(array $models, Collection $results, string $relation): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $attribute = $this->getDictionaryKey($this->getRelatedKeyFrom($result));

            $dictionary[$attribute] = $result;
        }

        foreach ($models as $model) {
            $attribute = $this->getDictionaryKey($this->getForeignKeyFrom($model));

            if (isset($dictionary[$attribute])) {
                $model->setRelation($relation, $dictionary[$attribute]);
            }
        }

        return $models;
    }

    /**
     * @param Model[] $models
     */
    public function initRelation(array $models, string $relation): array
    {
        foreach ($models as $model){
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    public function addConstraints()
    {
        // TODO: Implement addConstraints() method.
    }

    protected function newRelatedInstanceFor(Model $parent): Model
    {
        return $this->related->newInstance();
    }
}