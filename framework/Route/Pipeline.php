<?php

namespace Framework\Kernel\Route;

use Framework\Kernel\Contracts\Support\Responsable;
use Framework\Kernel\Foundation\Exceptions\Contracts\ExceptionHandlerInterface;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Requests\Request;
use Framework\Kernel\Pipeline\BasePipeline;
use Throwable;

class Pipeline extends BasePipeline
{
    protected function handleCarry(mixed $carry): mixed
    {
        return $carry instanceof Responsable
            ? $carry->toResponse($this->getContainer()->make(RequestInterface::class))
            : $carry;
    }

    protected function handleException(mixed $passable, Throwable $throwable): mixed
    {
        if (! $this->container->bound(ExceptionHandlerInterface::class) ||
            ! $passable instanceof Request) {
            throw $throwable;
        }

        /** @var ExceptionHandlerInterface $handler */
        $handler = $this->container->make(ExceptionHandlerInterface::class);

        $handler->report($throwable);

        $response = $handler->render($passable, $throwable);

        if (method_exists($response, 'withException')) {
            $response->withException($throwable);
        }

        return $this->handleCarry($response);
    }
}