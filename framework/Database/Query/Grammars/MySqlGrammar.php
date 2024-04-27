<?php

namespace Framework\Kernel\Database\Query\Grammars;

use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;

class MySqlGrammar extends Grammar
{
    protected array $operators = ['sounds like'];

    protected function whereNotNull(QueryBuilderInterface $query,array $where): string
    {
        $columnValue = (string) $this->getValue($where['column']);

        if ($this->isJsonSelector($columnValue)) {
            [$field, $path] = $this->wrapJsonFieldAndPath($columnValue);

            return '(json_extract('.$field.$path.') is not null AND json_type(json_extract('.$field.$path.')) != \'NULL\')';
        }

        return parent::whereNotNull($query, $where);
    }
}
