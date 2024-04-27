<?php

namespace Framework\Kernel\Database\Pagination;

use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Contracts\Support\Htmlable;
use Framework\Kernel\Contracts\Support\Renderable;
use Framework\Kernel\Database\Eloquent\EloquentCollection;
use Framework\Kernel\Database\Pagination\Contracts\LengthAwarePaginatorInterface;
use Framework\Kernel\Support\Collection;
use Framework\Kernel\View\ViewFactory;
use IteratorAggregate;

class LengthAwarePaginator extends AbstractPaginator implements LengthAwarePaginatorInterface, Arrayable, IteratorAggregate
{
    protected int $lastPage;

    public function __construct(
        mixed           $items,
        protected int   $total,
        protected int   $perPage,
        ?int            $currentPage = null,
        protected array $options = []
    )
    {
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->lastPage = max((int)ceil($total / $perPage), 1);
        $this->path = $this->path !== '/' ? rtrim($this->path, '/') : $this->path;
        $this->currentPage = $this->setCurrentPage($currentPage, $this->pageName);
        $this->items = $items instanceof Collection ? $items : Collection::make($items);
    }

    public function links(?string $view = null, array $data = []): ?Htmlable
    {
        return $this->render($view, $data);
    }


    public function toArray(): array
    {
        // TODO: Implement toArray() method.
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }

    public function render(?string $view = null, array $data = []): Htmlable
    {
        return static::viewFactory()->make($view ?: static::$defaultView, array_merge($data, [
            'paginator' => $this,
            'elements' => $this->elements(),
        ]));
    }


    protected function elements(): array
    {
        $window = UrlWindow::make($this);

        return array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);
    }
}