<?php

namespace Framework\Kernel\Database\Pagination\Contracts;

use Framework\Kernel\Contracts\Support\Htmlable;

interface PaginatorInterface
{
    public function render(?string $view = null,array $data = []): Htmlable;
}