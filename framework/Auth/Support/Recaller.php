<?php

namespace Framework\Kernel\Auth\Support;

class Recaller
{
    protected string $recaller;

    public function __construct(string $recaller)
    {
        $this->recaller = @unserialize($recaller, ['allowed_classes' => false]) ?: $recaller;
    }

    public function id(): string
    {
        return explode('|', $this->recaller, 3)[0];
    }

    public function token(): string
    {
        return explode('|', $this->recaller, 3)[1];
    }

    public function valid(): bool
    {
        return $this->properString() && $this->hasAllSegments();
    }

    protected function properString(): bool
    {
        return is_string($this->recaller) && str_contains($this->recaller, '|');
    }

    protected function hasAllSegments(): bool
    {
        $segments = explode('|', $this->recaller);

        return count($segments) >= 3 && trim($segments[0]) !== '' && trim($segments[1]) !== '';
    }
}