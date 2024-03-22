<?php

namespace Framework\Kernel\Database\Migrations;

abstract class Migration
{
    protected ?string $connection = null;

    public bool $withinTransaction = true;

    public function getConnection(): ?string
    {
        return $this->connection;
    }

    abstract public function up(): void;

    abstract public function down(): void;
}