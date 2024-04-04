<?php

namespace Framework\Kernel\Database\Schema;

use Closure;
use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Schema\Builder\SchemaBuilder;
use Framework\Kernel\Database\Schema\Grammar\SchemaGrammar;
use Framework\Kernel\Database\Schema\Support\ColumnDefinition;
use Framework\Kernel\Database\Schema\Support\ForeignIdColumnDefinition;
use Framework\Kernel\Database\Schema\Support\ForeignKeyDefinition;
use Framework\Kernel\Support\Fluent;


class Blueprint
{
    protected array $commands = [];


    protected array $columns = [];

    public bool $temporary = false;

    protected ?string $after = null;

    public ?string $charset = null;

    public ?string $collation = null;

    public function __construct(
        protected string $table,
        Closure          $callback = null,
    )
    {
        if ($callback) {
            $callback($this);
        }
    }

    public function build(ConnectionInterface $connection, SchemaGrammar $grammar): void
    {
        foreach ($this->toSql($connection, $grammar) as $statement) {
            $connection->statement($statement);
        }
    }

    public function toSql(ConnectionInterface $connection, SchemaGrammar $grammar): array
    {
       $this->addImpliedCommands($connection, $grammar);

        $statements = [];

        foreach ($this->commands as $command) {
            if ($command->shouldBeSkipped) {
                continue;
            }

            $method = 'compile' . ucfirst($command->name);

            if (!is_null($sql = $grammar->$method($this, $command, $connection))) {
                $statements = array_merge($statements, (array)$sql);
            }
        }

        return $statements;
    }

    protected function addImpliedCommands(ConnectionInterface $connection, SchemaGrammar $grammar): void
    {
        if (count($this->getAddedColumns()) > 0 && !$this->creating()) {
            array_unshift($this->commands, $this->createCommand('add'));
        }

        if (count($this->getChangedColumns()) > 0 && !$this->creating()) {
            array_unshift($this->commands, $this->createCommand('change'));
        }

        $this->addFluentIndexes();

        $this->addFluentCommands($connection, $grammar);
    }

    protected function addFluentCommands(ConnectionInterface $connection, SchemaGrammar $grammar): void
    {
        foreach ($this->columns as $column) {
            if ($column->change && !$connection->usingNativeSchemaOperations()) {
                continue;
            }

            foreach ($grammar->getFluentCommands() as $commandName) {
                $this->addCommand($commandName, compact('column'));
            }
        }
    }

    protected function addFluentIndexes(): void
    {
        foreach ($this->columns as $column) {

            foreach (['primary', 'unique', 'index', 'fulltext', 'fullText', 'spatialIndex'] as $index) {

                if ($column->{$index} === true) {
                    $this->{$index}($column->name);
                    $column->{$index} = null;

                    continue 2;
                }



                if ($column->{$index} === false && $column->chande) {
                    $this->{'drop' . ucfirst($index)}([$column->name]);

                    $column->{$index} = null;

                    continue 2;
                }

                if (isset($column->{$index})) {
                    $this->{$index}($column->name, $column->{$index});
                    $column->{$index} = null;

                    continue 2;
                }
            }
        }
    }

    public function dropIfExists(): Fluent
    {
        return $this->addCommand('dropIfExists');
    }

    public function getAddedColumns(): array
    {
        return array_filter($this->columns, function (ColumnDefinition $column) {
            return !$column->chande;
        });
    }

    public function getChangedColumns(): array
    {
        return array_filter($this->columns, function ($column) {
            return (bool)$column->change;
        });
    }

    public function creating(): bool
    {
        return collect($this->commands)->contains(function (Fluent $command) {
            return $command->name === 'create';
        });
    }

    protected function createCommand(string $name, array $parameters = []): Fluent
    {
        return new Fluent(array_merge(compact('name'), $parameters));
    }

    protected function addCommand(string $name, array $parameters = []): Fluent
    {
        $this->commands[] = $command = $this->createCommand($name, $parameters);

        return $command;
    }

    public function create(): Fluent
    {
        return $this->addCommand('create');
    }

    protected function addColumn(string $type, string $name, array $parameters = []): ColumnDefinition
    {
        return $this->addColumnDefinition(new ColumnDefinition(
            array_merge(compact('type', 'name'), $parameters)
        ));
    }

    protected function addColumnDefinition(ColumnDefinition $definition): ColumnDefinition
    {
        $this->columns[] = $definition;

        if ($this->after) {
            $definition->after($this->after);

            $this->after = $definition->name;
        }

        return $definition;
    }

    public function increments(string $column): ColumnDefinition
    {
        return $this->unsignedInteger($column, true);
    }

    public function id(string $column = 'id'): ColumnDefinition
    {
        return $this->bigIncrements($column);
    }

    public function bigIncrements(string $column): ColumnDefinition
    {
        return $this->unsignedBigInteger($column, true);
    }

    public function unsignedBigInteger(string $column, bool $autoIncrement = false): ColumnDefinition
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    public function bigInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): ColumnDefinition
    {
        return $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    public function unsignedInteger(string $column, bool $autoIncrement = false): ColumnDefinition
    {
        return $this->integer($column, $autoIncrement, true);
    }

    public function integer(string $column, bool $autoIncrement = false, bool $unsigned = false): ColumnDefinition
    {
        return $this->addColumn('integer', $column, compact('autoIncrement', 'unsigned'));
    }

    public function string(string $column, ?int $length = null): ColumnDefinition
    {
        $length = $length ?: SchemaBuilder::$defaultStringLength;

        return $this->addColumn('string', $column, compact('length'));
    }

    public function timestamps(int $precision = 0): void
    {
        $this->timestamp('created_at', $precision)->nullable();

        $this->timestamp('updated_at', $precision)->nullable();
    }

    public function timestamp($column, $precision = 0): ColumnDefinition
    {
        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    public function unique(array|string $columns,?string $name = null,?string $algorithm = null): Fluent
    {
        return $this->indexCommand('unique', $columns, $name, $algorithm);
    }

    public function foreignIdFor(Model|string $model, ?string $column = null): ForeignIdColumnDefinition
    {
        if(is_string($model)){
            $model = new $model;
        }

        $column = $column ?: $model->getForeignKey();

        if ($model->getKeyType() === 'int' && $model->getIncrementing()) {
            return $this->foreignId($column);
        }

//        $modelTraits = class_uses_recursive($model);
//
//        if (in_array(HasUlids::class, $modelTraits, true)) {
//            return $this->foreignUlid($column);
//        }
//
//        return $this->foreignUuid($column);
    }

    public function foreign(string|array $columns, ?string $name = null): ForeignKeyDefinition
    {
        $command = new ForeignKeyDefinition(
            $this->indexCommand('foreign', $columns, $name)->getAttributes()
        );

        $this->commands[count($this->commands) - 1] = $command;

        return $command;
    }

    protected function indexCommand(string $type, string|array $columns, ?string $index, ?string $algorithm = null): Fluent
    {
        $columns = (array) $columns;

        $index = $index ?: $this->createIndexName($type, $columns);

        return $this->addCommand(
            $type, compact('index', 'columns', 'algorithm')
        );
    }

    protected function createIndexName(string $type, array $columns): string
    {
        $index = strtolower($this->table.'_'.implode('_', $columns).'_'.$type);

        return str_replace(['-', '.'], '_', $index);
    }

    public function foreignId(string $column): ColumnDefinition
    {
        return $this->addColumnDefinition(new ForeignIdColumnDefinition($this, [
            'type' => 'bigInteger',
            'name' => $column,
            'autoIncrement' => false,
            'unsigned' => true,
        ]));
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    public function charset(string $charset): void
    {
        $this->charset = $charset;
    }

    public function collation(string $collation): void
    {
        $this->collation = $collation;
    }
}