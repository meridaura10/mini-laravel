<?php

namespace Framework\Kernel\Http\Responses\Factory\Contracts;

use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;
use Framework\Kernel\Http\Responses\JsonResponse;

interface ResponseFactoryInterface
{
    public function json(mixed $data = [],int $status = 200, array $headers = [],int $options = 0): JsonResponse;

    public function view(array|string $view,array $data = [],int $status = 200, array $headers = []): ResponseInterface;
}