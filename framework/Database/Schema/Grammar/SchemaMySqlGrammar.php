<?php

namespace Framework\Kernel\Database\Schema\Grammar;


use Framework\Kernel\Database\Connection;
use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Schema\Blueprint;
use Framework\Kernel\Support\Fluent;
use mysql_xdevapi\Expression;

class SchemaMySqlGrammar extends SchemaGrammar
{
    protected array $fluentCommands = ['AutoIncrementStartingValues'];

    protected array $modifiers = [
        'Unsigned', 'Charset', 'Collate', 'VirtualAs', 'StoredAs', 'Nullable',
        'Srid', 'Default', 'OnUpdate', 'Invisible', 'Increment', 'Comment', 'After', 'First',
    ];

    protected array $serials = ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'];

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

    public function compileAutoIncrementStartingValues(Blueprint $blueprint, Fluent $command)
    {
        if ($command->column->autoIncrement
            && $value = $command->column->get('startingValue', $command->column->get('from'))) {
            return 'alter table '.$this->wrapTable($blueprint).' auto_increment = '.$value;
        }
    }

    public function compileCreate(Blueprint $blueprint, Fluent $command, ConnectionInterface $connection): string
    {
        $sql = $this->compileCreateTable(
            $blueprint, $command, $connection,
        );

        $sql = $this->compileCreateEncoding(
            $sql, $connection, $blueprint
        );

        return $this->compileCreateEngine($sql, $connection, $blueprint);
    }

    protected function compileCreateEncoding(string $sql, ConnectionInterface $connection, Blueprint $blueprint): string
    {
        if (isset($blueprint->charset)) {
            $sql .= ' default character set '.$blueprint->charset;
        } elseif (! is_null($charset = $connection->getConfig('charset'))) {
            $sql .= ' default character set '.$charset;
        }

        if (isset($blueprint->collation)) {
            $sql .= " collate '{$blueprint->collation}'";
        } elseif (! is_null($collation = $connection->getConfig('collation'))) {
            $sql .= " collate '{$collation}'";
        }

        return $sql;
    }

    protected function compileCreateEngine(string $sql, Connection $connection, Blueprint $blueprint): string
    {
        if (isset($blueprint->engine)) {
            return $sql.' engine = '.$blueprint->engine;
        } elseif (! is_null($engine = $connection->getConfig('engine'))) {
            return $sql.' engine = '.$engine;
        }

        return $sql;
    }

    protected function compileCreateTable(Blueprint $blueprint,Fluent $command,ConnectionInterface $connection): string
    {
        $tableStructure = $this->getColumns($blueprint);

        if ($primaryKey = $this->getCommandByName($blueprint, 'primary')) {
            $tableStructure[] = sprintf(
                'primary key %s(%s)',
                $primaryKey->algorithm ? 'using '.$primaryKey->algorithm : '',
                $this->columnize($primaryKey->columns)
            );

            $primaryKey->shouldBeSkipped = true;
        }

        return sprintf('%s table %s (%s)',
            $blueprint->temporary ? 'create temporary' : 'create',
            $this->wrapTable($blueprint),
            implode(', ', $tableStructure)
        );
    }

    protected function modifyUnsigned(Blueprint $blueprint, Fluent $column): ?string
    {
        if ($column->unsigned) {
            return ' unsigned';
        }

        return null;
    }

    protected function modifyCharset(Blueprint $blueprint, Fluent $column): ?string
    {
        if (! is_null($column->charset)) {
            return ' character set '.$column->charset;
        }

        return null;
    }

    protected function modifyNullable(Blueprint $blueprint, Fluent $column): ?string
    {
        if (is_null($column->virtualAs) &&
            is_null($column->virtualAsJson) &&
            is_null($column->storedAs) &&
            is_null($column->storedAsJson)) {
            return $column->nullable ? ' null' : ' not null';
        }

        if ($column->nullable === false) {
            return ' not null';
        }

        return null;
    }

    protected function modifyDefault(Blueprint $blueprint, Fluent $column): ?string
    {
        if (! is_null($column->default)) {
            return ' default '.$this->getDefaultValue($column->default);
        }

        return null;
    }

    protected function modifyIncrement(Blueprint $blueprint, Fluent $column): ?string
    {
        if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            return ' auto_increment primary key';
        }

        return null;
    }

    protected function modifyAfter(Blueprint $blueprint, Fluent $column): ?string
    {
        if (! is_null($column->after)) {
            return ' after '.$this->wrap($column->after);
        }

        return null;
    }

    public function compileDropIfExists(Blueprint $blueprint, Fluent $command): string
    {
        return 'drop table if exists '.$this->wrapTable($blueprint);
    }
}