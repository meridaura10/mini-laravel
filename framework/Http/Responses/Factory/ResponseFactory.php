<?php

namespace Framework\Kernel\Http\Responses\Factory;

use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;
use Framework\Kernel\Http\Responses\Factory\Contracts\ResponseFactoryInterface;
use Framework\Kernel\Http\Responses\JsonResponse;
use Framework\Kernel\Http\Responses\Response;
use Framework\Kernel\View\Contracts\ViewFactoryInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    public function __construct(
        protected ViewFactoryInterface $view,
    )
    {

    }
    public function json(mixed $data = [], int $status = 200, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function make(mixed $content = '',int $status = 200, array $headers = []): ResponseInterface
    {
        return new Response($content, $status, $headers);
    }

    public function view(array|string $view, array $data = [], int $status = 200, array $headers = []): ResponseInterface
    {
        if (is_array($view)) {
            return $this->make($this->view->first($view, $data), $status, $headers);
        }

        return $this->make($this->view->make($view, $data), $status, $headers);
    }
}