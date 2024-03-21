<?php

namespace Framework\Kernel\Database\Eloquent;

use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
use Framework\Kernel\Database\Query\Support\Traits\ForwardsCallsTrait;
use Framework\Kernel\Database\Traits\BuildsQueriesTrait;

class Builder implements BuilderInterface
{
    use BuildsQueriesTrait, ForwardsCallsTrait;

    protected array $passthru = [
        'aggregate',
        'average',
        'avg',
        'count',
        'dd',
        'ddrawsql',
        'doesntexist',
        'doesntexistor',
        'dump',
        'dumprawsql',
        'exists',
        'existsor',
        'explain',
        'getbindings',
        'getconnection',
        'getgrammar',
        'implode',
        'insert',
        'insertgetid',
        'insertorignore',
        'insertusing',
        'max',
        'min',
        'raw',
        'rawvalue',
        'sum',
        'tosql',
        'torawsql',
    ];

    protected Model $model;

    public function __construct(
        protected QueryBuilderInterface $query,
    ) {

    }

    public function setModel(Model $model): static
    {
        $this->model = $model;

        $this->query->from($model->getTable());

        return $this;
    }

    public function create(array $attributes = []): Model
    {
        return tap($this->newModelInstance($attributes), function (Model $instance) {
            $instance->save();
        });
    }

    public function newModelInstance(array $attributes = []): Model
    {

        return $this->model->newInstance($attributes)->setConnection(
            $this->query->getConnection()->getName(),
        );
    }

    public function get(array $columns = ['*']): EloquentCollection
    {
        $builder = $this->applyScopes();

        if (count($models = $builder->getModels($columns)) > 0) {
            //            dd('eagerLoadRelations');
            //            $models = $builder->eagerLoadRelations($models);
        }

        return $builder->getModel()->newCollection($models);
    }

    public function getModels(array $columns = ['*']): array
    {
        return $this->model->hydrate(
            $this->query->get($columns)->all(),
        )->all();
    }

    public function hydrate(array $items): EloquentCollection
    {
        $instance = $this->newModelInstance();

        return $instance->newCollection(array_map(function ($item) use ($instance) {
            $model = $instance->newFromBuilder((array) $item);

            //            if (count($items) > 1) {
            //                $model->preventsLazyLoading = Model::preventsLazyLoading();
            //            }

            return $model;
        }, $items));
    }

    public static function with(array $relations): static
    {
        // TODO: Implement with() method.

    }

    public function withCount(mixed $relations): static
    {
        // TODO: Implement withCount() method.
    }

    public function getQuery(): QueryBuilderInterface
    {
        return $this->query;
    }

    public function toBase(): QueryBuilderInterface
    {
        return $this->applyScopes()->getQuery();
    }

    public function applyScopes(): static
    {
        return $this;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function __call($method, $parameters): mixed
    {
        if (in_array(strtolower($method), $this->passthru)) {
            return $this->toBase()->{$method}(...$parameters);
        }

        $this->forwardCallTo($this->query, $method, $parameters);

        return $this;
    }
}
