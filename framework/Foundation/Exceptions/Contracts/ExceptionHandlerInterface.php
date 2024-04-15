<?php

namespace Framework\Kernel\Foundation\Exceptions\Contracts;

use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;
use Throwable;

interface ExceptionHandlerInterface
{
    public function render(RequestInterface $request, Throwable $e): ResponseInterface;

    public function report(Throwable $e): void;
}