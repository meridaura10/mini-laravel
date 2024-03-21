<?php

namespace Framework\Kernel\Finder\Iterator;

use Closure;
use IteratorAggregate;
use Traversable;

class LazyIterator implements IteratorAggregate
{
    private Closure $iteratorFactory;

    public function __construct(callable $iteratorFactory)
    {
        $this->iteratorFactory = $iteratorFactory(...);
    }

    public function getIterator(): Traversable
    {
        yield from ($this->iteratorFactory)();
    }
}
