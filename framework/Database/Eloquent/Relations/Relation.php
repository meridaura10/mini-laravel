<?php

namespace Framework\Kernel\Database\Eloquent\Relations;

use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Query\Support\Traits\ForwardsCallsTrait;

abstract class Relation implements BuilderInterface
{
    use ForwardsCallsTrait;

    protected Model $related;

    public function __construct(
        protected BuilderInterface $query,
        protected Model $parent,
    ){
        $this->related = $query->getModel();

        $this->addConstraints();
    }

    abstract public function addConstraints();

    public function __call($method, $parameters): mixed
    {
        return $this->forwardDecoratedCallTo($this->query, $method, $parameters);
    }
}