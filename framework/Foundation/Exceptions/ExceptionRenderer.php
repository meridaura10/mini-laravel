<?php

namespace Framework\Kernel\Foundation\Exceptions;
use Framework\Kernel\Foundation\Exceptions\Contracts\ExceptionPageInterface;
use Framework\Kernel\Support\Arr;
use Throwable;
use Framework\Kernel\Foundation\Exceptions\Contracts\ExceptionRendererInterface;

class ExceptionRenderer implements ExceptionRendererInterface
{


    public function __construct(
        protected ExceptionPageInterface $page,
    ) {
    }

    public function render(Throwable $throwable): string
    {
        return $this->page->render($throwable);
    }
}