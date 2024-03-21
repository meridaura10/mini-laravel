<?php

namespace Framework\Kernel\Pipeline\Contracts;

use Closure;

interface PipelineInterface
{
    public function send(mixed $passable): static;

    public function through(mixed $pipes): static;

    public function via(string $method): static;

    public function then(Closure $destination): mixed;
}
