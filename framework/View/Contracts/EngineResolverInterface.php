<?php

namespace Framework\Kernel\View\Contracts;

interface EngineResolverInterface
{
    public function register(string $engine, callable $resolver): void;

    public function resolve(string $engine): EngineInterface;

    public function forget(string $engine): void;
}
