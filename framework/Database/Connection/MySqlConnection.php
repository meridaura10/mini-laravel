<?php

namespace Framework\Kernel\Database\Connection;

use Framework\Kernel\Database\Connection;
use Framework\Kernel\Database\Grammar;
use Framework\Kernel\Database\Query\Grammars\MySqlGrammar;
use Framework\Kernel\Database\Query\Processors\MySqlProcessor;
use Framework\Kernel\Database\Query\Processors\Processor;
use Framework\Kernel\Database\Schema\Builder\MySqlSchemaBuilder;
use Framework\Kernel\Database\Schema\Builder\SchemaBuilder;
use Framework\Kernel\Database\Schema\Grammar\SchemaGrammar;
use Framework\Kernel\Database\Schema\Grammar\SchemaMySqlGrammar;

class MySqlConnection extends Connection
{
    public function getDefaultQueryGrammar(): Grammar
    {
        return new MySqlGrammar;
    }

    public function getDefaultPostProcessor(): Processor
    {
        return new MySqlProcessor;
    }

    protected function getDefaultSchemaGrammar(): ?SchemaGrammar
    {
        ($grammar = new SchemaMySqlGrammar())->setConnection($this);

        return $grammar;
    }

    public function getSchemaBuilder(): SchemaBuilder
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new MySqlSchemaBuilder($this);
    }
}
