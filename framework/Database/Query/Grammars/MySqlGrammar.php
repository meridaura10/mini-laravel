<?php

namespace Framework\Kernel\Database\Query\Grammars;

class MySqlGrammar extends Grammar
{
    protected array $operators = ['sounds like'];
}
