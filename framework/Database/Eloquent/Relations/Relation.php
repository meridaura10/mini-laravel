<?php

namespace Framework\Kernel\Database\Eloquent\Relations;

use Closure;
use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Eloquent\EloquentCollection;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Query\Support\Traits\ForwardsCallsTrait;
use Framework\Kernel\Support\Collection;
use InvalidArgumentException;

abstract class Relation implements BuilderInterface
{
    use ForwardsCallsTrait;

    protected Model $related;

    protected static bool $constraints = true;
    protected bool $eagerKeysWereEmpty = false;

    protected static bool $requireMorphMap = false;

    public static array $morphMap = [];

    public function __construct(
        protected BuilderInterface $query,
        protected Model $parent,
    ){
        $this->related = $query->getModel();

        $this->addConstraints();
    }

    abstract public function addConstraints();

    abstract public function addEagerConstraints(array $models): void;

    abstract public function match(array $models, EloquentCollection $results,string $relation): array;

    abstract public function initRelation(array $models, string $relation): array;

    public function getRelationQuery(): BuilderInterface
    {
        return $this->query;
    }

    public static function requiresMorphMap(): bool
    {
        return static::$requireMorphMap;
    }

    public static function morphMap(?array $map = null,bool $merge = true): array
    {
        $map = static::buildMorphMapFromModels($map);

        if (is_array($map)) {
            static::$morphMap = $merge && static::$morphMap
                ? $map + static::$morphMap : $map;
        }

        return static::$morphMap;
    }

    protected static function buildMorphMapFromModels(array $models = null): ?array
    {
        if (is_null($models) || ! array_is_list($models)) {
            return $models;
        }

        return array_combine(array_map(function ($model) {
            return (new $model)->getTable();
        }, $models), $models);
    }

    public function getEager(): Collection
    {
        return $this->eagerKeysWereEmpty
            ? $this->query->getModel()->newCollection([])
            : $this->get();
    }

    protected function getKeys(array $models, $key = null): array
    {
        return collect($models)->map(function ($value) use ($key) {
            return $key ? $value->getAttribute($key) : $value->getKey();
        })->values()->unique(null, true)->sort()->all();
    }

    public static function noConstraints(Closure $callback)
    {
        $previous = static::$constraints;

        static::$constraints = false;

        try {
            return $callback();
        } finally {
            static::$constraints = $previous;
        }
    }

    protected function whereInMethod(Model $model, string $key): string
    {
        return $model->getKeyName() === last(explode('.', $key))
        && in_array($model->getKeyType(), ['int', 'integer'])
            ? 'whereIntegerInRaw'
            : 'whereIn';
    }


    protected function whereInEager(string $whereIn, string $key, array $modelKeys, $query = null): void
    {
        ($query ?? $this->query)->{$whereIn}($key, $modelKeys);

        if ($modelKeys === []) {
            $this->eagerKeysWereEmpty = true;
        }
    }

    protected function getRelatedKeyFrom(Model $model): mixed
    {
        return $model->{$this->ownerKey};
    }

    protected function getDictionaryKey($attribute)
    {
        if (is_object($attribute)) {
            if (method_exists($attribute, '__toString')) {
                return $attribute->__toString();
            }

            if ($attribute instanceof \UnitEnum) {
                return $attribute instanceof \BackedEnum ? $attribute->value : $attribute->name;
            }

            throw new InvalidArgumentException('Model attribute value is an object but does not have a __toString method.');
        }

        return $attribute;
    }


    public function __call($method, $parameters): mixed
    {
        return $this->forwardDecoratedCallTo($this->query, $method, $parameters);
    }
}