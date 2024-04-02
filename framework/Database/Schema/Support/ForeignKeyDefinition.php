<?php

namespace Framework\Kernel\Database\Schema\Support;

use Framework\Kernel\Support\Fluent;

/**
 * @method ForeignKeyDefinition on(string $table) Specify the referenced table
 * @method ForeignKeyDefinition onDelete(string $action) Add an ON DELETE action
 * @method ForeignKeyDefinition onUpdate(string $action) Add an ON UPDATE action
 * @method ForeignKeyDefinition references(string|array $columns) Specify the referenced column(s)
 */

class ForeignKeyDefinition extends Fluent
{
    public function cascadeOnDelete(): static
    {
        return $this->onDelete('cascade');
    }

    public function nullOnDelete(): static
    {
        return $this->onDelete('set null');
    }
}