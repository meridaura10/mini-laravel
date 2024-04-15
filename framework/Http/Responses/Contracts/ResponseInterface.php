<?php

namespace Framework\Kernel\Http\Responses\Contracts;

use Framework\Kernel\Http\Requests\Contracts\RequestInterface;

interface ResponseInterface
{
    public function send(): static;

    public function prepare(RequestInterface $request): static;

    public function getContent(): false|string;

    public function getStatusCode(): int;
}
