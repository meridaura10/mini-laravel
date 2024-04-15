<?php

namespace Framework\Kernel\Database\Eloquent\Relations;

use Framework\Kernel\Support\Collection;

class HasMany extends HasOneOrMany
{

    public function addConstraints()
    {
        // TODO: Implement addConstraints() method.
    }

    public function addEagerConstraints(array $models): void
    {
        // TODO: Implement addEagerConstraints() method.
    }

    public function match(array $models, Collection $results, string $relation): array
    {
        // TODO: Implement match() method.
    }

    public function initRelation(array $models, string $relation): array
    {
        // TODO: Implement initRelation() method.
    }
}