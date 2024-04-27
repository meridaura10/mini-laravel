<?php

namespace Framework\Kernel\Database\Traits;

use Framework\Kernel\Database\Eloquent\EloquentCollection;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Pagination\Contracts\LengthAwarePaginatorInterface;
use Framework\Kernel\Database\Pagination\LengthAwarePaginator;

trait BuildsQueriesTrait
{
    public function first(array $columns = ['*']): ?Model
    {
        return $this->take(1)->get($columns)->first();
    }

    protected function paginator(EloquentCollection $items,int $total,int $perPage,int $currentPage,array $options): LengthAwarePaginatorInterface
    {
        return app()->make(LengthAwarePaginator::class, compact(
            'items', 'total', 'perPage', 'currentPage', 'options'
        ));
    }
}
