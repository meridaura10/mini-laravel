<?php

namespace Framework\Kernel\View;

use Framework\Kernel\View\Contracts\EngineInterface;
use Framework\Kernel\View\Contracts\EngineResolverInterface;
use Framework\Kernel\View\Exceptions\InvalidArgumentException;

class EngineResolver implements EngineResolverInterface
{
    protected array $resolvers = [];

    protected array $resolved = [];

    public function register(string $engine, callable $resolver): void
    {
        $this->forget($engine);

        $this->resolvers[$engine] = $resolver;
    }

    public function resolve(string $engine): EngineInterface
    {
        if (isset($this->resolved[$engine])) {
            return $this->resolved[$engine];
        }

        if (isset($this->resolvers[$engine])) {
            return $this->resolved[$engine] = call_user_func($this->resolvers[$engine]);
        }

        throw new InvalidArgumentException("Engine [{$engine}] not found.");
    }

    public function forget(string $engine): void
    {
        unset($this->resolved[$engine]);
    }
}
