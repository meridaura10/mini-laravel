<?php

namespace Framework\Kernel\Database\Schema\Grammar;

use Framework\Kernel\Database\Grammar;

abstract class SchemaGrammar extends Grammar
{
    abstract public function compileTables(string $database): string;
}