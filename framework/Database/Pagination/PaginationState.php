<?php

namespace Framework\Kernel\Database\Pagination;

use Framework\Kernel\Application\Contracts\ApplicationInterface;

class PaginationState
{
    public static function resolveUsing(ApplicationInterface $app): void
    {
        Paginator::viewFactoryResolver(fn () => $app['view']);

        Paginator::currentPathResolver(fn () => $app['request']->url());

        Paginator::currentPageResolver(function ($pageName = 'page') use ($app) {
            $page = $app['request']->input($pageName);

            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
                return (int) $page;
            }

            return 1;
        });

        Paginator::queryStringResolver(fn () => $app['request']->query());

//        CursorPaginator::currentCursorResolver(function ($cursorName = 'cursor') use ($app) {
//            return Cursor::fromEncoded($app['request']->input($cursorName));
//        });
    }
}