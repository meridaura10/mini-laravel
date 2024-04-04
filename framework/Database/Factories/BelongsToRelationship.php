<?php

namespace Framework\Kernel\Database\Factories;


use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Eloquent\Relations\MorphTo;
use Framework\Kernel\Support\Collection;

class BelongsToRelationship
{
    protected mixed $resolved = null;

    public function __construct(
        protected Factory|Model $factory,
        protected string $relationship,
    )
    {

    }

    public function recycle(Collection $recycle): static
    {
        if ($this->factory instanceof Factory) {
            $this->factory = $this->factory->recycle($recycle);
        }

        return $this;
    }

    public function attributesFor(Model $model): array
    {
        $relationship = $model->{$this->relationship}();

        return $relationship instanceof MorphTo ? [
//            $relationship->getMorphType() => $this->factory instanceof Factory ? $this->factory->newModel()->getMorphClass() : $this->factory->getMorphClass(),
//            $relationship->getForeignKeyName() => $this->resolver($relationship->getOwnerKeyName()),
        ] : [
            $relationship->getForeignKeyName() => $this->resolver($relationship->getOwnerKeyName()),
        ];
    }

    protected function resolver(?string $key): \Closure
    {
        return function () use ($key) {
            if (! $this->resolved) {
                $instance = $this->factory instanceof Factory
                    ? ($this->factory->getRandomRecycledModel($this->factory->modelName()) ?? $this->factory->create()->first())
                    : $this->factory;

                return $this->resolved = $key ? $instance->{$key} : $instance->getKey();
            }

            return $this->resolved;
        };
    }
}