<?php

namespace Framework\Kernel\Database\Pagination;

use Framework\Kernel\Database\Pagination\Contracts\LengthAwarePaginatorInterface;

class UrlWindow
{


    public function __construct(
        protected LengthAwarePaginatorInterface $paginator,
    ) {

    }

    public static function make(LengthAwarePaginator $paginator): array
    {
        return (new static($paginator))->get();
    }

    public function get(): array
    {
        $onEachSide = $this->paginator->onEachSide;

        if ($this->lastPage() < ($onEachSide * 2) + 8) {
            return $this->getSmallSlider();
        }

        return $this->getUrlSlider($onEachSide);
    }

    protected function getSmallSlider(): array
    {
        return [
            'first' => $this->paginator->getUrlRange(1, $this->lastPage()),
            'slider' => null,
            'last' => null,
        ];
    }

    protected function lastPage(): int
    {
        return $this->paginator->lastPage();
    }
}