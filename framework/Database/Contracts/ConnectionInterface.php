<?php

namespace Framework\Kernel\Database\Contracts;

use Closure;
use Framework\Kernel\Database\Query\QueryBuilder;
use Framework\Kernel\Database\Schema\Grammar\SchemaGrammar;
use PDO;

interface ConnectionInterface
{
    public function query(): QueryBuilderInterface;

    public function setReadWriteType(?string $type = null): static;

    public function setReconnector(callable $reconnector): static;

    public function getRawPdo(): Closure|PDO;

    public function getRawReadPdo(): Closure|PDO|null;

    public function getName(): ?string;

    public function insert(string $query, array $bindings = []): bool;

    public function getSchemaBuilder(): SchemaBuilderInterface;

    public function getSchemaGrammar(): SchemaGrammar;

    public function selectFromWriteConnection(string $query,array $bindings = []): array;

    public function getConfig(?string $option = null): null|string|array;

    public function getDatabaseName(): string;

    public function statement(string $query,array $bindings = []): bool;

    public function table(QueryBuilderInterface|ExpressionInterface|string $table, ?string $as = null): QueryBuilderInterface;
}

//{
//    /**
//     * Begin a fluent query against a database table.
//     *
//     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $table
//     * @param  string|null  $as
//     * @return \Illuminate\Database\Query\Builder
//     */
//    public function table($table, $as = null);
//
//    /**
//     * Get a new raw query expression.
//     *
//     * @param  mixed  $value
//     * @return \Illuminate\Contracts\Database\Query\Expression
//     */
//    public function raw($value);
//
//    /**
//     * Run a select statement and return a single result.
//     *
//     * @param  string  $query
//     * @param  array  $bindings
//     * @param  bool  $useReadPdo
//     * @return mixed
//     */
//    public function selectOne($query, $bindings = [], $useReadPdo = true);
//
//    /**
//     * Run a select statement against the database.
//     *
//     * @param  string  $query
//     * @param  array  $bindings
//     * @param  bool  $useReadPdo
//     * @return array
//     */
//    public function select($query, $bindings = [], $useReadPdo = true);
//
//    /**
//     * Run a select statement against the database and returns a generator.
//     *
//     * @param  string  $query
//     * @param  array  $bindings
//     * @param  bool  $useReadPdo
//     * @return \Generator
//     */
//    public function cursor($query, $bindings = [], $useReadPdo = true);
//
//    /**
//     * Run an insert statement against the database.
//     *
//     * @param  string  $query
//     * @param  array  $bindings
//     * @return bool
//     */
//    public function insert($query, $bindings = []);
//
//    /**
//     * Run an update statement against the database.
//     *
//     * @param  string  $query
//     * @param  array  $bindings
//     * @return int
//     */
//    public function update($query, $bindings = []);
//
//    /**
//     * Run a delete statement against the database.
//     *
//     * @param  string  $query
//     * @param  array  $bindings
//     * @return int
//     */
//    public function delete($query, $bindings = []);
//
//    /**
//     * Execute an SQL statement and return the boolean result.
//     *
//     * @param  string  $query
//     * @param  array  $bindings
//     * @return bool
//     */
//    public function statement($query, $bindings = []);
//
//    /**
//     * Run an SQL statement and get the number of rows affected.
//     *
//     * @param  string  $query
//     * @param  array  $bindings
//     * @return int
//     */
//    public function affectingStatement($query, $bindings = []);
//
//    /**
//     * Run a raw, unprepared query against the PDO connection.
//     *
//     * @param  string  $query
//     * @return bool
//     */
//    public function unprepared($query);
//
//    /**
//     * Prepare the query bindings for execution.
//     *
//     * @param  array  $bindings
//     * @return array
//     */
//    public function prepareBindings(array $bindings);
//
//
//    public function transaction(Closure $callback,int $attempts = 1): mixed;
//
//
//    public function beginTransaction();
//
//    /**
//     * Commit the active database transaction.
//     *
//     * @return void
//     */
//    public function commit(): void;
//
//
//    public function rollBack(): void;
//
//    public function transactionLevel(): int;
//
//
//    public function pretend(Closure $callback): array;
//
//    public function getDatabaseName(): string;
//}
