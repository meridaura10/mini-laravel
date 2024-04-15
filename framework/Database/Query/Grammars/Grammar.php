<?php

namespace Framework\Kernel\Database\Query\Grammars;

use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
use Framework\Kernel\Database\Eloquent\Builder;
use Framework\Kernel\Database\Query\JoinClause;
use Framework\Kernel\Database\Query\QueryBuilder;
use Framework\Kernel\Support\Arr;

class Grammar extends \Framework\Kernel\Database\Grammar
{
    protected array $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'indexHint',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'lock',
    ];

    protected ?array $original = null;

    public function compileInsertGetId(QueryBuilder $query, array $values, string $sequence): string
    {
        return $this->compileInsert($query, $values);
    }

    public function prepareBindingsForDelete(array $bindings): array
    {
        return Arr::flatten(
            Arr::except($bindings, 'select')
        );
    }

    public function compileDelete(QueryBuilderInterface $query)
    {
        $table = $this->wrapTable($query->from);

        $where = $this->compileWheres($query);

        return trim(
            isset($query->joins)
                ? $this->compileDeleteWithJoins($query, $table, $where)
                : $this->compileDeleteWithoutJoins($query, $table, $where)
        );
    }

    protected function whereNested(QueryBuilderInterface $query,array $where):string
    {
        $offset = $where['query'] instanceof JoinClause ? 3 : 6;

        return '('.substr($this->compileWheres($where['query']), $offset).')';
    }

    protected function compileDeleteWithoutJoins(QueryBuilderInterface $query,string $table,string $where): string
    {
        return "delete from {$table} {$where}";
    }

    public function compileInsert(QueryBuilderInterface $query, array $values): string
    {
        $table = $this->wrapTable($query->from);

        if (empty($values)) {
            return "insert into {$table} default values";
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        $columns = $this->columnize(array_keys(reset($values)));

        $parameters = implode(', ', array_map(function ($record) {
            return '(' . implode(',', array_fill(0, count($record), '?')) . ')';
        }, $values));

        return "insert into $table ($columns) values $parameters";
    }

    protected function whereInRaw(QueryBuilderInterface $query, array $where): string
    {
        if (! empty($where['values'])) {
            return $this->wrap($where['column']).' in ('.implode(', ', $where['values']).')';
        }

        return '0 = 1';
    }

    public function compileSelect(QueryBuilder $query): string
    {
        if (($query->unions || $query->havings) && $query->aggregate) {
            return $this->compileUnionAggregate($query);
        }

        $original = $query->columns;

        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        $sql = trim($this->concatenate(
            $this->compileComponents($query))
        );

        if ($query->unions) {
            $sql = $this->wrapUnion($sql).' '.$this->compileUnions($query);
        }

        $query->columns = $original;


        return $sql;
    }

    protected function compileUnionAggregate(QueryBuilderInterface $query): string
    {
        $sql = $this->compileAggregate($query, $query->aggregate);

        $query->aggregate = [];

        return $sql . ' from (' . $this->compileSelect($query) . ') as ' . $this->wrapTable('temp_table');
    }

    protected function compileAggregate(QueryBuilderInterface $query, array $aggregate): string
    {
        $column = $this->columnize($aggregate['columns']);

        if (is_array($query->distinct)) {
            $column = 'distinct ' . $this->columnize($query->distinct);
        } elseif ($query->distinct && $column !== '*') {
            $column = 'distinct ' . $column;
        }

        return 'select ' . $aggregate['function'] . '(' . $column . ') as aggregate';
    }

    protected function compileComponents(QueryBuilderInterface $query): array
    {
        $sql = [];


        try {
            foreach ($this->selectComponents as $component) {
                if (!empty($query->{$component})) {
                    $method = 'compile' . ucfirst($component);
                    $sql[$component] = $this->{$method}($query, $query->{$component});
                }
            }
        }catch (\Throwable $exception){
            throw new \Exception($exception->getMessage());
        }

        return $sql;
    }

    public function compileWheres(QueryBuilderInterface $query): string
    {
        $sql = $this->compileWheresToArray($query);

        if (count($sql = $this->compileWheresToArray($query)) > 0) {
            return $this->concatenateWhereClauses($query, $sql);
        }

        return '';
    }

    protected function concatenateWhereClauses(QueryBuilderInterface $query,array $sql): string
    {
        $conjunction = $query instanceof JoinClause ? 'on' : 'where';

        return $conjunction.' '.$this->removeLeadingBoolean(implode(' ', $sql));
    }

    protected function removeLeadingBoolean(string $value): string
    {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    protected function compileWheresToArray(QueryBuilderInterface $query): array
    {
        return collect($query->wheres)->map(function ($where) use ($query) {
            return $where['boolean'].' '.$this->{"where{$where['type']}"}($query, $where);
        })->all();
    }

    protected function whereBasic(QueryBuilderInterface $query,array $where): string
    {
        $value = $this->parameter($where['value']);

        $operator = str_replace('?', '??', $where['operator']);

        return $this->wrap($where['column']).' '.$operator.' '.$value;
    }

    protected function compileFrom(QueryBuilderInterface $query, $table): string
    {
        return 'from ' . $this->wrapTable($table);
    }

    protected function compileColumns(QueryBuilderInterface $query, $columns): string
    {
        if (! empty($query->aggregate)) {
            return '';
        }


        if ($query->distinct) {
            $select = 'select distinct ';
        } else {
            $select = 'select ';
        }

        return $select . $this->columnize($columns);
    }

    protected function compileOrders(QueryBuilderInterface $query,array $orders): string
    {
        if (! empty($orders)) {
            return 'order by '.implode(', ', $this->compileOrdersToArray($query, $orders));
        }

        return '';
    }

    protected function compileOrdersToArray(QueryBuilderInterface $query,array $orders): array
    {
        return array_map(function ($order) {
            return $order['sql'] ?? $this->wrap($order['column']).' '.$order['direction'];
        }, $orders);
    }

    protected function compileLimit(QueryBuilderInterface $query, $limit): string
    {
        return 'limit ' . (int)$limit;
    }

    protected function concatenate(array $segments): string
    {
        return implode(' ', array_filter($segments, function ($value) {
            return (string)$value !== '';
        }));
    }

    public function parameter(mixed $value): mixed
    {
        return $this->isExpression($value) ? $this->getValue($value) : '?';
    }

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
