<?php

namespace Framework\Kernel\Database;

use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Contracts\ExpressionInterface;
use Framework\Kernel\Database\Query\QueryBuilder;
use Framework\Kernel\Database\Schema\Blueprint;

abstract class Grammar
{
    protected ?ConnectionInterface $connection = null;

    public function setConnection(ConnectionInterface $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    public function wrapTable(string|ExpressionInterface|Blueprint $table): string
    {
        if (! $this->isExpression($table)) {
            return $this->wrap($table, true);
        }

        return $this->getValue($table);
    }

    public function wrap($value, $prefixAlias = false): string
    {
        if ($this->isExpression($value)) {
            return $this->getValue($value);
        }

        return $this->wrapSegments(explode('.', $value));
    }

    public function wrapSegments(mixed $segments): string
    {
        return implode('.', array_map(function ($segment, $key) use ($segments) {
            return $key == 0 && count($segments) > 1
                ? $this->wrapTable($segment)
                : $this->wrapValue($segment);
        }, $segments, array_keys($segments)));
    }

    protected function wrapValue(string $value): string
    {
        if ($value !== '*') {
            return '`'.str_replace('`', '``', $value).'`';
        }

        return $value;
    }

    public function quoteString(string|array $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map([$this, __FUNCTION__], $value));
        }

        return "'$value'";
    }

    protected function columnize(array $columns): string
    {
        return implode(', ', array_map([$this, 'wrap'], $columns));
    }

    public function getValue(ExpressionInterface|string|int|float $expression): string|int|float
    {
        if ($this->isExpression($expression)) {
            return $this->getValue($expression->getValue($this));
        }

        return $expression;
    }

    public function isExpression(string|ExpressionInterface $value): bool
    {
        return $value instanceof ExpressionInterface;
    }

    public function compileInsertGetId(QueryBuilder $query, array $values, string $sequence): string
    {
        return $this->compileInsert($query, $values);
    }
}
