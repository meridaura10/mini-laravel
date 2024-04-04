<?php

namespace Framework\Kernel\Database\Eloquent;

use Closure;
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

    protected \Closure $onDelete;

    protected array $eagerLoad = [];

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

    public function delete(): mixed
    {
        if (isset($this->onDelete)) {
            return call_user_func($this->onDelete, $this);
        }

        return $this->toBase()->delete();
    }


    public function withCount(mixed $relations): static
    {
        return $this;
    }

    public function with(string|array $relations, null|Closure|string $callback = null): static
    {
        if ($callback instanceof Closure) {
            $eagerLoad = $this->parseWithRelations([$relations => $callback]);
        } else {
            $eagerLoad = $this->parseWithRelations(is_string($relations) ? func_get_args() : $relations);
        }

        $this->eagerLoad = array_merge($this->eagerLoad, $eagerLoad);

        return $this;
    }

    public function parseWithRelations(array $relations): array
    {
        if ($relations === []) {
            return [];
        }

        $results = [];

        foreach ($this->prepareNestedWithRelationships($relations) as $name => $constraints){
            $results = $this->addNestedWiths($name, $results);

            $results[$name] = $constraints;
        }

        dd($results);

        return $results;
    }

    protected function addNestedWiths(string $name, array $results): array
    {
        $progress = [];

        foreach (explode('.', $name) as $segment) {
            $progress[] = $segment;

            if (! isset($results[$last = implode('.', $progress)])) {
                $results[$last] = static function () {};
            }
        }

        return $results;
    }

    protected function prepareNestedWithRelationships(array $relations, string $prefix = ''): array
    {
        $preparedRelationships = [];

        if ($prefix !== '') {
            $prefix .= '.';
        }

        foreach ($relations as $key => $value){
            if (! is_string($key) || ! is_array($value)) {
                continue;
            }
        }

        foreach ($relations as $key => $value) {
            if (is_numeric($key) && is_string($value)) {
                [$key, $value] = $this->convertNameToCallableArray($value);
            }



            $preparedRelationships[$prefix.$key] = $this->combineConstraints([
                $value,
                $preparedRelationships[$prefix.$key] ?? static function () {},
            ]);
        }

        return $preparedRelationships;
    }

    protected function combineConstraints(array $constraints): Closure
    {
        return function ($builder) use ($constraints) {
            foreach ($constraints as $constraint) {
                $builder = $constraint($builder) ?? $builder;
            }

            return $builder;
        };
    }

    protected function convertNameToCallableArray(string $name): array
    {
        return [$name, static function () {}];
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
