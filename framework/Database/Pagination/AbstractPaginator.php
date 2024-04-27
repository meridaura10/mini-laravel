<?php

namespace Framework\Kernel\Database\Pagination;

use Closure;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Collection;
use Framework\Kernel\View\Contracts\ViewFactoryInterface;
use Traversable;

class AbstractPaginator
{
    protected Collection $items;

    protected string $path = '/';

    protected ?string $fragment = null;

    protected string $pageName = 'page';

    protected int $currentPage;

    protected array $query = [];

    protected static ?Closure $currentPageResolver = null;

    protected static ?Closure $currentPathResolver = null;

    protected static ?Closure $queryStringResolver = null;

    protected static ?Closure $viewFactoryResolver;

    public static string $defaultView = 'pagination::tailwind';

    public int $onEachSide = 3;



    public static function resolveCurrentPage(string $pageName = 'page',int $default = 1): int
    {
        if (isset(static::$currentPageResolver)) {
            return (int) call_user_func(static::$currentPageResolver, $pageName);
        }

        return $default;
    }

    public static function currentPageResolver(Closure $resolver): void
    {
        static::$currentPageResolver = $resolver;
    }

    public static function viewFactory(): ViewFactoryInterface
    {
        return call_user_func(static::$viewFactoryResolver);
    }

    public static function viewFactoryResolver(Closure $resolver): void
    {
        static::$viewFactoryResolver = $resolver;
    }

    public static function resolveCurrentPath(string $default = '/')
    {
        if (isset(static::$currentPathResolver)) {
            return call_user_func(static::$currentPathResolver);
        }

        return $default;
    }

    public static function currentPathResolver(Closure $resolver): void
    {
        static::$currentPageResolver = $resolver;
    }

    protected function setCurrentPage(int $currentPage,string $pageName): int
    {
        $currentPage = $currentPage ?: static::resolveCurrentPage($pageName);

        return $this->isValidPageNumber($currentPage) ? (int) $currentPage : 1;
    }

    protected function isValidPageNumber($page): bool
    {
        return $page >= 1 && filter_var($page, FILTER_VALIDATE_INT) !== false;
    }

    public function getUrlRange(int $start,int $end): array
    {
        return collect(range($start, $end))->mapWithKeys(function ($page) {
            return [$page => $this->url($page)];
        })->all();
    }

    public function url(int $page): string
    {
        if ($page <= 0) {
            $page = 1;
        }

        $parameters = [$this->pageName => $page];

        if (count($this->query) > 0) {
            $parameters = array_merge($this->query, $parameters);
        }

        return $this->path()
            .(str_contains($this->path(), '?') ? '&' : '?')
            .Arr::query($parameters)
            .$this->buildFragment();
    }

    protected function buildFragment(): string
    {
        return $this->fragment ? '#'.$this->fragment : '';
    }

    public function path(): string
    {
        return $this->path;
    }

    public function getIterator(): Traversable
    {
        return $this->items->getIterator();
    }

    public function setCollection(Collection $collection): static
    {
        $this->items = $collection;

        return $this;
    }

    public static function queryStringResolver(Closure $resolver): void
    {
        static::$queryStringResolver = $resolver;
    }

    protected function addQuery(string $key,string $value): static
    {
        if ($key !== $this->pageName) {
            $this->query[$key] = $value;
        }

        return $this;
    }
}