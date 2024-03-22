<?php

namespace Framework\Kernel\Database\Query;

use Closure;
use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Contracts\ExpressionInterface;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
use Framework\Kernel\Database\Eloquent\Builder;
use Framework\Kernel\Database\Grammar;
use Framework\Kernel\Database\Query\Processors\Processor;
use Framework\Kernel\Database\Traits\BuildsQueriesTrait;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Collection;

class QueryBuilder implements QueryBuilderInterface
{
    use BuildsQueriesTrait;

    public array $bindings = [
        'select' => [],
        'from' => [],
        'join' => [],
        'where' => [],
        'groupBy' => [],
        'having' => [],
        'order' => [],
        'union' => [],
        'unionOrder' => [],
    ];

    public array $havings = [];

    public array $aggregate = [];
    public ?array $orders = null;


    protected Grammar $grammar;

    protected Processor $processor;

    public ?string $from = null;

    public ?string $unions = null;

    public ?int $limit = null;

    public ?int $unionLimit = null;

    public ?array $columns = null;

    public bool $distinct = false;

    public bool $useWritePdo = false;

    public function __construct(
        protected ConnectionInterface $connection,
        ?Grammar                      $grammar = null,
        ?Processor                    $processor = null
    )
    {
        $this->grammar = $grammar ?: $connection->getQueryGrammar();
        $this->processor = $processor ?: $connection->getPostProcessor();
    }

    public function from(string $table): static
    {
        $this->from = $table;

        return $this;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function take(int $value): static
    {
        return $this->limit($value);
    }

    public function limit(int $value): static
    {
        $property = $this->unions ? 'unionLimit' : 'limit';

        if ($value >= 0) {
            $this->$property = $value > 0 ? $value : null;
        }

        return $this;
    }

    public function get(array $columns = ['*']): Collection
    {
        return collect($this->onceWithColumns(Arr::wrap($columns), function () {
            return $this->processor->processSelect($this, $this->runSelect());
        }));
    }

    protected function onceWithColumns(array $columns, callable $callback)
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        $result = $callback();

        $this->columns = $original;

        return $result;
    }

    protected function runSelect(): array
    {
        return $this->connection->select(
            $this->toSql(), $this->getBindings(), !$this->useWritePdo,
        );
    }

    public function toSql(): string
    {
        return $this->grammar->compileSelect($this);
    }

    public function getBindings(): array
    {
        return Arr::flatten($this->bindings);
    }

    public function insertGetId(array $values, ?string $sequence = null): int
    {
        $sql = $this->grammar->compileInsertGetId($this, $values, $sequence);

        return $this->processor->processInsertGetId($this, $sql, $values, $sequence);
    }

    public function useWritePdo(): static
    {
        $this->useWritePdo = true;

        return $this;
    }

    public function orderBy(BuilderInterface|Closure|string|ExpressionInterface|QueryBuilderInterface $column, bool $direction = true): static
    {
        $direction = $direction ? 'asc' : 'desc';

        if ($this->isQueryable($column)) {
            dd('query builder 147 isQueryable true');
        }

        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    protected function isQueryable(mixed $value): bool
    {
        return $value instanceof self ||
            $value instanceof BuilderInterface ||
            $value instanceof Closure;
//            $value instanceof Relation ||
    }

    public function pluck(string|ExpressionInterface $column, ?string $key = null): Collection
    {
        $queryResult = $this->onceWithColumns(
            is_null($key) ? [$column] : [$column, $key],
            function () {
                return $this->processor->processSelect(
                    $this, $this->runSelect()
                );
            }
        );

        return collect();
    }

    public function cloneWithout(array $properties): static
    {
        return tap($this->clone(), function (self $clone) use ($properties) {
            foreach ($properties as $property) {
                $clone->{$property} = null;
            }
        });
    }

    public function cloneWithoutBindings(array $except): static
    {
        return tap($this->clone(), function (self $clone) use ($except) {
            foreach ($except as $type) {
                $clone->bindings[$type] = [];
            }
        });
    }

    protected function setAggregate(string $function, array $columns): static
    {
        $this->aggregate = compact('function', 'columns');

        if (empty($this->groups)) {
            $this->orders = null;

            $this->bindings['order'] = [];
        }

        return $this;
    }

    public function aggregate(string $function, array $columns = ['*']): mixed
    {
        $results = $this->cloneWithout($this->unions || $this->havings ? [] : ['columns'])
                         ->cloneWithoutBindings($this->unions || $this->havings ? [] : ['select'])
                         ->setAggregate($function, $columns)
                          ->get($columns);

        if (! $results->isEmpty()) {
            return array_change_key_case((array) $results[0])['aggregate'];
        }

        return 0;
    }

    public function max(string|ExpressionInterface $column): mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    public function clone(): static
    {
        return clone $this;
    }
}
