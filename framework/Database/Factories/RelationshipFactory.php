<?php

namespace Framework\Kernel\Database\Factories;

use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Eloquent\Relations\BelongsToMany;
use Framework\Kernel\Database\Eloquent\Relations\HasOneOrMany;
use Framework\Kernel\Database\Eloquent\Relations\MorphOneOrMany;
use Framework\Kernel\Support\Collection;

class RelationshipFactory
{
    public function __construct(
        protected Factory $factory,
        protected string $relationship,
    ){

    }

    public function createFor(Model $parent): void
    {
        $relationship = $parent->{$this->relationship}();
        if ($relationship instanceof MorphOneOrMany) {
            $this->factory->state([
                $relationship->getMorphType() => $relationship->getMorphClass(),
                $relationship->getForeignKeyName() => $relationship->getParentKey(),
            ])->create([], $parent);
        } else
      if ($relationship instanceof HasOneOrMany) {
            $this->factory->state([
                $relationship->getForeignKeyName() => $relationship->getParentKey(),
            ])->create([], $parent);
        } elseif ($relationship instanceof BelongsToMany) {
            $relationship->attach($this->factory->create([], $parent));
        }
    }

    public function recycle(Collection $recycle): static
    {
        $this->factory = $this->factory->recycle($recycle);

        return $this;
    }
}