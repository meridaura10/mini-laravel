<?php

namespace Framework\Kernel\Http\Responses\Factory;

use Framework\Kernel\Http\Responses\Factory\Contracts\ResponseFactoryInterface;
use Framework\Kernel\Http\Responses\JsonResponse;

class ResponseFactory implements ResponseFactoryInterface
{
    public function json(mixed $data = [], int $status = 200, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }
}