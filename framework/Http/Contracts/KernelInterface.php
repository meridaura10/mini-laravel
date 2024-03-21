<?php

namespace Framework\Kernel\Http\Contracts;

use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;

interface KernelInterface
{
    public function handle(RequestInterface $request): ResponseInterface;
}
