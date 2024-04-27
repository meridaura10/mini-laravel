<?php

namespace Framework\Kernel\Database\Pagination\Contracts;

interface LengthAwarePaginatorInterface extends PaginatorInterface
{
    public function lastPage(): int;

    public function getUrlRange(int $start,int $end): array;
}