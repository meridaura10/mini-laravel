<?php

namespace Framework\Kernel\Database\Schema\Support;

use Framework\Kernel\Database\Schema\Blueprint;
use Framework\Kernel\Database\Schema\Support\ColumnDefinition;
use Framework\Kernel\Support\Str;

class ForeignIdColumnDefinition extends ColumnDefinition
{
    public function __construct(
        protected Blueprint $blueprint,
        array $attributes,
    )
    {
        parent::__construct($attributes);
    }

    public function constrained(?string $table = null,string $column = 'id',?string $indexName = null): ForeignKeyDefinition
    {
        return $this->references($column, $indexName)->on($table ?? Str::of($this->name)->beforeLast('_'.$column)->plural());
    }

    public function references($column, $indexName = null): ForeignKeyDefinition
    {
        return $this->blueprint->foreign($this->name, $indexName)->references($column);
    }
}