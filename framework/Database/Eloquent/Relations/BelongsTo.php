<?php

namespace Framework\Kernel\Database\Eloquent\Relations;

use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Eloquent\Model;

class BelongsTo extends Relation
{

    public function addConstraints()
    {
        // TODO: Implement addConstraints() method.
    }

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
}