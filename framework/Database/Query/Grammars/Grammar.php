<?php

namespace Framework\Kernel\Database\Query\Grammars;

use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
use Framework\Kernel\Database\Query\QueryBuilder;

class Grammar extends \Framework\Kernel\Database\Grammar
{
    protected array $selectComponents = [
        //        'aggregate',
        'columns',
        'from',
        //        'indexHint',
        //        'joins',
        //        'wheres',
        //        'groups',
        //        'havings',
        //        'orders',
        'limit',
        //        'offset',
        //        'lock',
    ];

    protected ?array $original = null;

    public function compileInsertGetId(QueryBuilder $query, array $values, string $sequence): string
    {
        return $this->compileInsert($query, $values);
    }

    protected function compileInsert(QueryBuilder $query, array $values): string
    {
        $table = $this->wrapTable($query->from);

        if (empty($values)) {
            return "insert into {$table} default values";
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        $columns = $this->columnize(array_keys(reset($values)));

        $parameters = implode(', ', array_map(function ($record) {
            return '('.implode(',', array_fill(0, count($record), '?')).')';
        }, $values));

        return "insert into $table ($columns) values $parameters";
    }

    public function compileSelect(QueryBuilder $query): string
    {
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

    protected function compileComponents(QueryBuilderInterface $query): array
    {
        $sql = [];

        foreach ($this->selectComponents as $component) {
            if (isset($query->$component)) {
                $method = 'compile'.ucfirst($component);

                $sql[$component] = $this->$method($query, $query->$component);
            }
        }

        return $sql;
    }

    protected function compileFrom(QueryBuilderInterface $query, $table): string
    {
        return 'from '.$this->wrapTable($table);
    }

    protected function compileColumns(QueryBuilderInterface $query, $columns): string
    {
        if ($query->distinct) {
            $select = 'select distinct ';
        } else {
            $select = 'select ';
        }

        return $select.$this->columnize($columns);
    }

    protected function compileLimit(QueryBuilderInterface $query, $limit): string
    {
        return 'limit '.(int) $limit;
    }

    protected function concatenate(array $segments): string
    {
        return implode(' ', array_filter($segments, function ($value) {
            return (string) $value !== '';
        }));
    }

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
