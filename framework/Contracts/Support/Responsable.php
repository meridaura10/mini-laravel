<?php

namespace Framework\Kernel\Contracts\Support;

use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;

interface Responsable
{
    public function toResponse(RequestInterface $request): ResponseInterface;
}
