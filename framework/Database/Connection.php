<?php

namespace Framework\Kernel\Database;

use Closure;
use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Contracts\ExpressionInterface;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
use Framework\Kernel\Database\Contracts\SchemaBuilderInterface;
use Framework\Kernel\Database\Exceptions\LostConnectionException;
use Framework\Kernel\Database\Exceptions\QueryException;
use Framework\Kernel\Database\Query\Grammars\Grammar as QueryGrammar;
use Framework\Kernel\Database\Query\Processors\Processor;
use Framework\Kernel\Database\Query\QueryBuilder;
use Framework\Kernel\Database\Schema\Builder\SchemaBuilder;
use Framework\Kernel\Database\Schema\Grammar\SchemaGrammar;
use Framework\Kernel\Support\Arr;
use PDO;
use PDOStatement;

class Connection implements ConnectionInterface
{
    protected ?Processor $postProcessor = null;

    protected ?Grammar $queryGrammar = null;

    protected ?SchemaGrammar $schemaGrammar = null;

    protected static array $resolvers = [];

    protected ?Closure $reconnector = null;

    protected null|Closure|PDO $readPdo = null;

    protected ?string $readWriteType = null;

    protected bool $recordsModified = false;

    protected int $fetchMode = PDO::FETCH_OBJ;

    protected int $transactions = 0;

    protected bool $readOnWriteConnection = false;

    public function __construct(
        protected Closure|PDO $pdo,
        protected string $database,
        protected array $config,
    ) {
        $this->useDefaultQueryGrammar();
        $this->useDefaultPostProcessor();
    }

    public function useDefaultPostProcessor(): void
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    protected function getDefaultPostProcessor(): Processor
    {
        return new Processor();
    }

    protected function getDefaultSchemaGrammar(): ?SchemaGrammar
    {
        return null;
    }

    public function getPostProcessor(): Processor
    {
        return $this->postProcessor;
    }

    public function useDefaultSchemaGrammar(): void
    {
        $this->schemaGrammar = $this->getDefaultSchemaGrammar();
    }

    public function useDefaultQueryGrammar(): void
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    protected function getDefaultQueryGrammar(): Grammar
    {
        ($grammar = new QueryGrammar)->setConnection($this);

        return $grammar;
    }

    public function query(): QueryBuilderInterface
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    public function getQueryGrammar(): Grammar
    {
        return $this->queryGrammar;
    }

    public function delete(string $query,array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function affectingStatement(string $query,array $bindings = []): int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            $this->recordsHaveBeenModified(
                ($count = $statement->rowCount()) > 0
            );

            return $count;
        });
    }

    public function select($query, $bindings = [],bool $useReadPdo = true, $test = false)
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo, $test){

            $statement = $this->prepared($this->getPdoForSelect()->prepare($query));

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            return $statement->fetchAll();
        });
    }

    protected function getPdoForSelect($useReadPdo = true): PDO
    {
        return $useReadPdo ? $this->getReadPdo() : $this->getPdo();
    }

    public function getReadPdo()
    {
        if ($this->transactions > 0) {
            return $this->getPdo();
        }

        if ($this->readOnWriteConnection ||
            ($this->recordsModified && $this->getConfig('sticky'))) {
            return $this->getPdo();
        }

        if ($this->readPdo instanceof Closure) {
            return $this->readPdo = call_user_func($this->readPdo);
        }

        return $this->readPdo ?: $this->getPdo();
    }

    protected function prepared(PDOStatement $statement)
    {
        $statement->setFetchMode($this->fetchMode);

        return $statement;
    }

    public function insert(string $query, array $bindings = []): bool
    {
        return $this->statement($query, $bindings);
    }

    public function statement(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $this->recordsHaveBeenModified();

            return $statement->execute();
        });
    }

        protected function bindValues(\PDOStatement $statement, array $bindings): void
        {
            foreach ($bindings as $key => $value) {
                $statement->bindValue(
                    is_string($key) ? $key : $key + 1,
                    $value,
                    match (true) {
                        is_int($value) => PDO::PARAM_INT,
                        is_resource($value) => PDO::PARAM_LOB,
                        default => PDO::PARAM_STR
                    },
                );
            }
        }

    protected function prepareBindings(array $bindings): array
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif (is_bool($value)) {
                $bindings[$key] = (int) $value;
            }
        }

        return $bindings;
    }

    public function recordsHaveBeenModified(bool $value = true): void
    {
        if (! $this->recordsModified) {
            $this->recordsModified = $value;
        }
    }

    public function getPdo(): PDO
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    protected function run(string $query, array $bindings, Closure $callback): mixed
    {
        $this->reconnectIfMissingConnection();

        try {
            return $callback($query, $bindings);
        } catch (QueryException $e) {
            throw $e;
            //            $result = $this->handleQueryException(
            //                $e, $query, $bindings, $callback
            //            );
        }

        return $result;
    }

    public function reconnectIfMissingConnection(): void
    {
        if (is_null($this->pdo)) {
            $this->reconnect();
        }
    }

    public function reconnect(): mixed
    {
        if (is_callable($this->reconnector)) {
            return call_user_func($this->reconnector, $this);
        }

        throw new LostConnectionException('Lost connection and no reconnector available.');
    }

    public function setQueryGrammar(Grammar $grammar): static
    {
        $this->queryGrammar = $grammar;

        return $this;
    }

    public function setPostProcessor(Processor $processor): static
    {
        $this->postProcessor = $processor;

        return $this;
    }

    public function setReconnector(callable $reconnector): static
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    public function getRawPdo(): Closure|PDO
    {
        return $this->pdo;
    }

    public function getRawReadPdo(): Closure|PDO|null
    {
        return $this->readPdo;
    }

    public function setReadPdo(PDO|Closure|null $pdo): static
    {
        $this->readPdo = $pdo;

        return $this;
    }

    public static function getResolver(string $driver)
    {
        return static::$resolvers[$driver] ?? null;
    }

    public function getName(): ?string
    {
        return $this->getConfig('name') ?? $this->getConfig('driver');
    }

    public function getDatabaseName(): string
    {
        return $this->getConfig('database');
    }

    public function getConfig(?string $option = null): null|string|array
    {
        return Arr::get($this->config, $option);
    }

    public function setReadWriteType(?string $type = null): static
    {
        $this->readWriteType = $type;

        return $this;
    }

    public function getSchemaBuilder(): SchemaBuilderInterface
    {
        if(!$this->schemaGrammar){
            $this->useDefaultSchemaGrammar();
        }

        return new SchemaBuilder($this);
    }

    public function getSchemaGrammar(): SchemaGrammar
    {
        return $this->schemaGrammar;
    }

    public function selectFromWriteConnection(string $query, array $bindings = []): array
    {
        return $this->select($query,$bindings,false);
    }

    public function usingNativeSchemaOperations(): bool
    {
        return SchemaBuilder::$alwaysUsesNativeSchemaOperationsIfPossible;
    }

    public function table(QueryBuilderInterface|string|ExpressionInterface $table, ?string $as = null): QueryBuilderInterface
    {
        return $this->query()->from($table, $as);
    }

    public function transaction(callable $callback, int $attempts = 1): mixed
    {
        throw new \Exception($callback,'method transaction or Migrator 134 line');
    }
}
