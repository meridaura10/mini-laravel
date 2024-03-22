<?php

namespace Framework\Kernel\Database\Migrations;

use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Contracts\ConnectionResolverInterface;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
use Framework\Kernel\Database\Contracts\SchemaBuilderInterface;
use Framework\Kernel\Database\Migrations\Contracts\MigrationRepositoryInterface;
use Framework\Kernel\Database\Query\QueryBuilder;
use Framework\Kernel\Database\Schema\Blueprint;
use Framework\Kernel\Database\Schema\Builder\SchemaBuilder;

class DatabaseMigrationRepository implements MigrationRepositoryInterface
{
    protected ?string $connection = null;

    public function __construct(
        protected ConnectionResolverInterface $resolver,
        protected string $table,
    ){

    }

    public function setSource(?string $name): void
    {
        $this->connection = $name;
    }

    public function repositoryExists(): bool
    {
        return $this->getSchemaBuilder()->hasTable($this->table);
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->resolver->connection($this->connection);
    }

    public function createRepository(): void
    {
       $this->getSchemaBuilder()->create($this->table,function (Blueprint $table) {
           $table->increments('id');
           $table->string('migration');
           $table->integer('batch');
       });
    }

    private function getSchemaBuilder(): SchemaBuilderInterface
    {
        return $this->getConnection()->getSchemaBuilder();
    }

    public function getRan(): array
    {
       return $this->table()
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->pluck('migration')->all();
    }

    protected function table(): QueryBuilderInterface
    {
        return $this->getConnection()->table($this->table)->useWritePdo();
    }

    public function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    public function getLastBatchNumber(): int
    {
        return $this->table()->max('batch');
    }
}