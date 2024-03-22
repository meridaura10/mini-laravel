<?php

namespace Framework\Kernel\Database\Contracts;

use Closure;
use Framework\Kernel\Database\Eloquent\Builder;
use Framework\Kernel\Database\Query\QueryBuilder;
use Framework\Kernel\Support\Collection;

interface QueryBuilderInterface
{
    public function getConnection(): ConnectionInterface;

    public function insertGetId(array $values, ?string $sequence = null): int;

    public function from(string $table): static;

    public function useWritePdo(): static;

    public function orderBy(QueryBuilderInterface|BuilderInterface|ExpressionInterface|Closure|string $column,bool $direction = true): static;

    public function pluck(ExpressionInterface|string $column,?string $key = null): Collection;

    public function max(string|ExpressionInterface $column): mixed;
}
