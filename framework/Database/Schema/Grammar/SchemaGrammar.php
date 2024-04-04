<?php

namespace Framework\Kernel\Database\Schema\Grammar;

use Framework\Kernel\Database\Contracts\ExpressionInterface;
use Framework\Kernel\Database\Grammar;
use Framework\Kernel\Database\Schema\Blueprint;
use Framework\Kernel\Support\Fluent;
use Framework\Kernel\Support\Stringable;

abstract class SchemaGrammar extends Grammar
{
    protected array $fluentCommands = [];

    protected array $modifiers = [];

    protected bool $transactions = false;

    abstract public function compileTables(string $database): string;

    public function getFluentCommands(): array
    {
        return $this->fluentCommands;
    }

    protected function getColumns(Blueprint $blueprint): array
    {
        $columns = [];

        foreach ($blueprint->getAddedColumns() as $column){
            $sql = $this->wrap($column).' '.$this->getType($column);

            $columns[] = $this->addModifiers($sql, $blueprint, $column);
        }

        return $columns;
    }

    protected function getType(Fluent $column)
    {
        return $this->{'type'.ucfirst($column->type)}($column);
    }

    protected function addModifiers($sql, Blueprint $blueprint, Fluent $column)
    {
        foreach ($this->modifiers as $modifier) {
            if (method_exists($this, $method = "modify{$modifier}")) {
                $modify = $this->{$method}($blueprint, $column);

                if($modify){
                    $sql .= $modify;
                }
            }
        }

        return $sql;


    }



    public function wrapTable(string|ExpressionInterface|Blueprint|Stringable $table): string
    {
        return parent::wrapTable(
            $table instanceof Blueprint ? $table->getTable() : $table
        );
    }

    public function wrap($value, $prefixAlias = false): string
    {
        return parent::wrap(
            $value instanceof Fluent ? $value->name : $value, $prefixAlias
        );
    }

    protected function getCommandByName(Blueprint $blueprint, $name): ?Fluent
    {
        $commands = $this->getCommandsByName($blueprint, $name);

        if (count($commands) > 0) {
            return reset($commands);
        }

        return null;
    }

    protected function getCommandsByName(Blueprint $blueprint, $name): array
    {
        return array_filter($blueprint->getCommands(), function ($value) use ($name) {
            return $value->name == $name;
        });
    }

    public function compileForeign(Blueprint $blueprint, Fluent $command)
    {
        $sql = sprintf('alter table %s add constraint %s ',
            $this->wrapTable($blueprint),
            $this->wrap($command->index)
        );

        $sql .= sprintf('foreign key (%s) references %s (%s)',
            $this->columnize($command->columns),
            $this->wrapTable($command->on),
            $this->columnize((array) $command->references)
        );

        if (! is_null($command->onDelete)) {
            $sql .= " on delete {$command->onDelete}";
        }

        if (! is_null($command->onUpdate)) {
            $sql .= " on update {$command->onUpdate}";
        }

        return $sql;
    }


    protected function typeBigInteger(Fluent $column): string
    {
        return 'bigint';
    }

    protected function typeString(Fluent $column): string
    {
        return "varchar({$column->length})";
    }

    protected function typeInteger(Fluent $column): string
    {
        return 'int';
    }

    public function typeTimestamp(Fluent $column): string
    {
        return 'timestamp';
    }

    public function supportsSchemaTransactions(): bool
    {
        return $this->transactions;
    }
}