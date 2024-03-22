<?php

namespace Framework\Kernel\Database\Schema\Builder;

use Closure;
use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Contracts\SchemaBuilderInterface;
use Framework\Kernel\Database\Schema\Blueprint;
use Framework\Kernel\Database\Schema\Grammar\SchemaGrammar;

class SchemaBuilder implements SchemaBuilderInterface
{
    public static int $defaultStringLength = 255;

    public static bool $alwaysUsesNativeSchemaOperationsIfPossible = false;

    protected SchemaGrammar $grammar;

    public function __construct(
        protected ConnectionInterface $connection,
    )
    {
        $this->grammar = $connection->getSchemaGrammar();
    }

    public function hasTable(string $name): bool
    {
        foreach ($this->getTables() as $value) {
            if (strtolower($name) === strtolower($value['name'])) {
                return true;
            }
        }

        return false;
    }

    public function getTables(): array
    {
        return $this->connection->getPostProcessor()->processTables(
            $this->connection->selectFromWriteConnection(
                $this->grammar->compileTables($this->connection->getDatabaseName())
            )
        );
    }

    public function create(string $table, Closure $callback): void
    {
        $this->build(tap($this->createBlueprint($table), function (Blueprint $blueprint) use ($callback){
            $blueprint->create();

            $callback($blueprint);
        }));
    }

    protected function createBlueprint(string $table, Closure $callback = null): Blueprint
    {
         return new Blueprint($table, $callback);
    }

    protected function build(Blueprint $blueprint): void
    {
        $blueprint->build($this->connection, $this->grammar);
    }
}