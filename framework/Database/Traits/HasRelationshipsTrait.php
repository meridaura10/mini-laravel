<?php

namespace Framework\Kernel\Database\Traits;

use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Eloquent\Relations\BelongsTo;
use Framework\Kernel\Database\Eloquent\Relations\HasMany;
use Framework\Kernel\Database\Eloquent\Relations\HasOne;
use Framework\Kernel\Support\Str;

trait HasRelationshipsTrait
{
    protected array $relations = [];

    public function setRelation(string $relation,mixed $value): static
    {
        $this->relations[$relation] = $value;

        return $this;
    }

    public function newRelatedInstance(string $class): Model
    {
        return tap(new $class, function ($instance) {
            if (! $instance->getConnectionName()) {
                $instance->setConnection($this->connection);
            }
        });
    }

    protected function guessBelongsToRelation(): string
    {
        [$one, $two, $caller] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return $caller['function'];
    }

    public function hasMany(string $related,?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasMany(
            $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }

    public function hasOne($related, $foreignKey = null, $localKey = null): HasOne
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasOne($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
    }

    protected function newHasOne(BuilderInterface $query, Model $parent,string $foreignKey,string $localKey): HasOne
    {
        return new HasOne($query, $parent, $foreignKey, $localKey);
    }

    public function belongsTo(string $related,?string $foreignKey = null,?string $ownerKey = null,?string $relation = null): BelongsTo
    {
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        $instance = $this->newRelatedInstance($related);

        if (is_null($foreignKey)) {
            $foreignKey = Str::snake($relation).'_'.$instance->getKeyName();
        }

        $ownerKey = $ownerKey ?: $instance->getKeyName();

        return $this->newBelongsTo(
            $instance->newQuery(), $this, $foreignKey, $ownerKey, $relation
        );
    }



    protected function newHasMany(BuilderInterface $query, Model $parent,string $foreignKey,string $localKey): HasMany
    {
        return new HasMany($query, $parent, $foreignKey, $localKey);
    }

    protected function newBelongsTo(BuilderInterface $query, Model $child,string $foreignKey,string $ownerKey,string $relation): BelongsTo
    {
        return new BelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
    }
}