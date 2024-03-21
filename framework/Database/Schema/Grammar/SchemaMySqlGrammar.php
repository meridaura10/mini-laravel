<?php

namespace Framework\Kernel\Database\Schema\Grammar;


class SchemaMySqlGrammar extends SchemaGrammar
{
    public function compileTables(string $database): string
    {
        return sprintf(
            'select table_name as `name`, (data_length + index_length) as `size`, '
            .'table_comment as `comment`, engine as `engine`, table_collation as `collation` '
            ."from information_schema.tables where table_schema = %s and table_type = 'BASE TABLE' "
            .'order by table_name',
            $this->quoteString($database)
        );
    }
}