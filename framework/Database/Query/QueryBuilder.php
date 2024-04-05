<?php

namespace Framework\Kernel\Database\Query;

use BackedEnum;
use Closure;
use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Contracts\ExpressionInterface;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
use Framework\Kernel\Database\Eloquent\Builder;
use Framework\Kernel\Database\Eloquent\Relations\Relation;
use Framework\Kernel\Database\Expression;
use Framework\Kernel\Database\Grammar;
use Framework\Kernel\Database\Query\Processors\Processor;
use Framework\Kernel\Database\Traits\BuildsQueriesTrait;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Collection;
use Framework\Kernel\View\Exceptions\InvalidArgumentException;

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

    public array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>', '&~', 'is', 'is not',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    public array $havings = [];

    public array $wheres = [];

    public array $aggregate = [];
    public ?array $orders = null;


    protected Grammar $grammar;

    protected Processor $processor;

    public ?string $from = null;

    public ?string $unions = null;

    public ?int $limit = null;

    public ?int $unionLimit = null;

    public ?array $columns = null;

    public bool|array $distinct = false;

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

    public function select($columns = ['*']): static
    {
        $this->columns = [];
        $this->bindings['select'] = [];

        $columns = is_array($columns) ? $columns : func_get_args();

        foreach ($columns as $as => $column) {
            if (is_string($as) && $this->isQueryable($column)) {
                $this->selectSub($column, $as);
            } else {
                $this->columns[] = $column;
            }
        }

        return $this;
    }

//    public function selectSub(QueryBuilderInterface|BuilderInterface|string $query,string $as): static
//    {
//        [$query, $bindings] = $this->createSub($query);
//
//        return $this->selectRaw(
//            '('.$query.') as '.$this->grammar->wrap($as), $bindings
//        );
//    }

    protected function runSelect(): array
    {
        return $this->connection->select(
            $this->toSql(), $this->getBindings(), !$this->useWritePdo
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

        $values = $this->cleanBindings($values);

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
    }

    public function where(string|ExpressionInterface $column,mixed $operator = null,mixed $value = null,string $boolean = 'and'): static
    {
        if ($column instanceof ExpressionInterface) {
            $type = 'Expression';

            $this->wheres[] = compact('type', 'column', 'boolean');

            return $this;
        }

        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '='];
        }

        $type = 'Basic';


        $this->wheres[] = compact(
            'type', 'column', 'operator', 'value', 'boolean'
        );

        if (! $value instanceof ExpressionInterface) {
            $this->addBinding($this->flattenValue($value));
        }

        return $this;
    }

    protected function flattenValue(mixed $value): mixed
    {
        return is_array($value) ? head(Arr::flatten($value)) : $value;
    }

    public function addBinding(mixed $value,string $type = 'where'): static
    {
        if (! array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}.");
        }

        if (is_array($value)) {
            $this->bindings[$type] = array_values(array_map(
                [$this, 'castBinding'],
                array_merge($this->bindings[$type], $value),
            ));
        } else {
            $this->bindings[$type][] = $this->castBinding($value);
        }

        return $this;
    }

    public function whereIntegerInRaw(string $column, Arrayable|array $values, string $boolean = 'and', bool $not = false): static
    {
        $type = $not ? 'NotInRaw' : 'InRaw';

        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        $values = Arr::flatten($values);

        foreach ($values as &$value) {
            $value = (int) $value;
        }

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        return $this;
    }

    protected function invalidOperator(string $operator): string
    {
        return (! in_array(strtolower($operator), $this->operators, true) &&
                ! in_array(strtolower($operator), $this->grammar->getOperators(), true));
    }

    public function prepareValueAndOperator(mixed $value,string $operator,bool $useDefault = false): array
    {
        if ($useDefault) {
            return [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new \InvalidArgumentException('Illegal operator and value combination.');
        }

        return [$value, $operator];
    }

    protected function invalidOperatorAndValue(string $operator,mixed $value): bool
    {
        return is_null($value) && in_array($operator, $this->operators) &&
            ! in_array($operator, ['=', '<>', '!=']);
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

        if (empty($queryResult)) {
            return collect();
        }

        $key = $this->stripTableForPluck($key);

        $column = $this->stripTableForPluck($column);

        return is_array($queryResult[0])
            ? $this->pluckFromArrayColumn($queryResult, $column, $key)
            : $this->pluckFromObjectColumn($queryResult, $column, $key);
    }

    protected function pluckFromObjectColumn(array $queryResult,string $column, ?string $key = null): Collection
    {
        $results = [];

        if (is_null($key)) {
            foreach ($queryResult as $row) {
                $results[] = $row->$column;
            }
        } else {
            foreach ($queryResult as $row) {
                $results[$row->$key] = $row->$column;
            }
        }

        return collect($results);
    }

    public function delete(mixed $id = null): int
    {
        if (! is_null($id)) {
            $this->where($this->from.'.id', '=', $id);
        }

        return $this->connection->delete(
            $this->grammar->compileDelete($this), $this->cleanBindings(
            $this->grammar->prepareBindingsForDelete($this->bindings)
        ));
    }



    protected function stripTableForPluck(?string $column = null): ?string
    {
        if (is_null($column)) {
            return $column;
        }

        $columnString = $column instanceof ExpressionInterface
            ? $this->grammar->getValue($column)
            : $column;

        $separator = str_contains(strtolower($columnString), ' as ') ? ' as ' : '\.';

        $parts = preg_split('~'.$separator.'~i', $columnString);
        return end($parts);
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

    public function insert(array $values): bool
    {
        if(empty($values)){
            return true;
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                ksort($value);

                $values[$key] = $value;
            }
        }

        return $this->connection->insert(
            $this->grammar->compileInsert($this, $values),
            $this->cleanBindings(Arr::flatten($values, 1)),
        );
    }

    public function castBinding($value)
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    public function cleanBindings(array $bindings): array
    {
        return collect($bindings)
            ->reject(function ($binding) {
                return $binding instanceof ExpressionInterface;
            })
            ->map([$this, 'castBinding'])
            ->values()
            ->all();
    }
}
