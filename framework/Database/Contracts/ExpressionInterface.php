<?php

namespace Framework\Kernel\Database\Contracts;

use Framework\Kernel\Database\Grammar;

interface ExpressionInterface
{
    public function getValue(Grammar $grammar): string|int|float;
}
