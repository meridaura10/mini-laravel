<?php

namespace Framework\Kernel\Http\Exception;

interface HttpExceptionInterface extends \Throwable
{
    public function getStatusCode(): int;

    public function getHeaders(): array;
}