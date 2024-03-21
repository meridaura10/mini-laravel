<?php

namespace Framework\Kernel\Database\Migrations\Contracts;

interface MigrationRepositoryInterface
{
    public function setSource(?string $name): void;

    public function repositoryExists(): bool;
}