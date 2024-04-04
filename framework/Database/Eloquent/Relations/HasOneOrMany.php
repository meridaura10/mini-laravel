<?php

namespace Framework\Kernel\Database\Eloquent\Relations;

use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Eloquent\Model;

abstract class HasOneOrMany extends Relation
{
    public function __construct(BuilderInterface $query, Model $parent,protected string $foreignKey,protected string $localKey)
    {
        parent::__construct($query, $parent);
    }

    public function getForeignKeyName(): string
    {
        $segments = explode('.', $this->getQualifiedForeignKeyName());

        return end($segments);
    }

    public function getQualifiedForeignKeyName(): string
    {
        return $this->foreignKey;
    }

    public function getParentKey(): mixed
    {
        return $this->parent->getAttribute($this->localKey);
    }
}