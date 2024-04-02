<?php

namespace Framework\Kernel\Database\Migrations\Contracts;

interface MigrationRepositoryInterface
{
    public function setSource(?string $name): void;

    public function repositoryExists(): bool;

    public function getRan(): array;

    public function delete(object $migration): void;

    public function getNextBatchNumber(): int;

    public function getLastBatchNumber(): int;

    public function log(string $file,int $batch): void;
}