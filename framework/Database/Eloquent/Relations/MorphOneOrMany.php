<?php

namespace Framework\Kernel\Database\Eloquent\Relations;

use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Eloquent\Model;

abstract class MorphOneOrMany extends HasOneOrMany
{
    protected string $morphClass;

    public function __construct(
        BuilderInterface $query,
        Model            $parent,
        protected string $morphType,
        string           $id,
        string           $localKey,
    )
    {
        $this->morphClass = $parent->getMorphClass();

        parent::__construct($query, $parent, $id, $localKey);
    }

    protected function setForeignAttributesForCreate(Model $model): void
    {
        $model->{$this->getForeignKeyName()} = $this->getParentKey();

        $model->{$this->getMorphType()} = $this->morphClass;
    }

    public function addConstraints(): void
    {
        if (static::$constraints) {
            $this->getRelationQuery()->where($this->morphType, $this->morphClass);

            parent::addConstraints();
        }
    }

    public function addEagerConstraints(array $models): void
    {
        parent::addEagerConstraints($models);

        $this->getRelationQuery()->where($this->morphType, $this->morphClass);
    }

    public function getMorphType(): string
    {
        return last(explode('.', $this->morphType));
    }

    public function getMorphClass(): string
    {
        return $this->morphClass;
    }
}