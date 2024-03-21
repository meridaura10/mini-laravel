<?php

namespace Framework\Kernel\Database\Query;

use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
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

    protected Grammar $grammar;

    protected Processor $processor;

    public ?string $from = null;

    public ?string $unions = null;

    public ?int $limit = null;

    public ?int $unionLimit = null;

    public ?array $columns = null;

    public bool $distinct = false;

    public function __construct(
        protected ConnectionInterface $connection,
        ?Grammar $grammar = null,
        ?Processor $processor = null
    ) {
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
            $this->toSql(), $this->getBindings()
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
}
