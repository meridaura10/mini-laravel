<?php

namespace Framework\Kernel\Database\Migrations\Contracts;

interface MigrationRepositoryInterface
{
    public function setSource(?string $name): void;

    public function repositoryExists(): bool;

    public function getRan(): array;

    public function getNextBatchNumber(): int;

    public function getLastBatchNumber(): int;

}