<?php

namespace Framework\Kernel\Database\Pagination;

use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Contracts\Support\Htmlable;
use Framework\Kernel\Database\Pagination\Contracts\PaginatorInterface;
use IteratorAggregate;
use Traversable;

class Paginator extends AbstractPaginator implements PaginatorInterface, Arrayable, IteratorAggregate
{
    public function toArray(): array
    {
        // TODO: Implement toArray() method.
    }

    public function render(?string $view = null, array $data = []): Htmlable
    {
        // TODO: Implement render() method.
    }
}