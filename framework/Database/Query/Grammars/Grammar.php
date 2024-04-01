<?php

namespace Framework\Kernel\Database\Query\Grammars;

use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
use Framework\Kernel\Database\Query\QueryBuilder;

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

    public function compileInsert(QueryBuilder $query, array $values): string
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

        foreach ($this->selectComponents as $component) {
            if (isset($query->$component) && !empty($query->$component)) {
                $method = 'compile' . ucfirst($component);

                $sql[$component] = $this->$method($query, $query->$component);
            }
        }

        return $sql;
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

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
