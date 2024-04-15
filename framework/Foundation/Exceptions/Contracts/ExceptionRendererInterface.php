<?php

namespace Framework\Kernel\Foundation\Exceptions\Contracts;

use Throwable;

interface ExceptionRendererInterface
{
    public function render(Throwable $throwable): string;
}