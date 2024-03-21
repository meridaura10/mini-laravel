<?php

namespace Framework\Kernel\Facades\Services;

use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Contracts\SchemaBuilderInterface;
use Framework\Kernel\Facades\Facade;

/**
 * @method static void defaultStringLength(int $length)
 * @method static void defaultMorphKeyType(string $type)
 * @method static void morphUsingUuids()
 * @method static void morphUsingUlids()
 * @method static void useNativeSchemaOperationsIfPossible(bool $value = true)
 * @method static bool createDatabase(string $name)
 * @method static bool dropDatabaseIfExists(string $name)
 * @method static bool hasTable(string $table)
 * @method static bool hasView(string $view)
 * @method static array getTables()
 * @method static array getViews()
 * @method static array getTypes()
 * @method static bool hasColumn(string $table, string $column)
 * @method static bool hasColumns(string $table, array $columns)
 * @method static void whenTableHasColumn(string $table, string $column, \Closure $callback)
 * @method static void whenTableDoesntHaveColumn(string $table, string $column, \Closure $callback)
 * @method static string getColumnType(string $table, string $column, bool $fullDefinition = false)
 * @method static array getColumnListing(string $table)
 * @method static array getColumns(string $table)
 * @method static array getIndexes(string $table)
 * @method static array getForeignKeys(string $table)
 * @method static void table(string $table, \Closure $callback)
 * @method static void create(string $table, \Closure $callback)
 * @method static void drop(string $table)
 * @method static void dropIfExists(string $table)
 * @method static void dropColumns(string $table, string|array $columns)
 * @method static void dropAllTables()
 * @method static void dropAllViews()
 * @method static void dropAllTypes()
 * @method static void rename(string $from, string $to)
 * @method static bool enableForeignKeyConstraints()
 * @method static bool disableForeignKeyConstraints()
 * @method static mixed withoutForeignKeyConstraints(\Closure $callback)
 * @method static \Framework\Kernel\Database\Connection getConnection()
 * @method static \Framework\Kernel\Database\Contracts\SchemaBuilderInterface setConnection(\Framework\Kernel\Database\Contracts\ConnectionInterface $connection)
 * @method static void blueprintResolver(\Closure $resolver)
 *
 * @see \Framework\Kernel\Database\Contracts\SchemaBuilderInterface
 */



class Schema extends Facade
{
    protected static bool $cached = false;

    public function connection(?string $name): SchemaBuilderInterface
    {
        return static::$app['db']->connection($name)->getSchemaBuilder();
    }

    protected static function getFacadeAccessor(): string
    {
        return 'db.schema';
    }
}