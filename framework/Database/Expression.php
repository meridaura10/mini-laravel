<?php

namespace Framework\Kernel\Database;

use Framework\Kernel\Database\Contracts\ExpressionInterface;

class Expression implements ExpressionInterface
{

    public function getValue(Grammar $grammar): string|int|float
    {
        // TODO: Implement getValue() method.
    }
}