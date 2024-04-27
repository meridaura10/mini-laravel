<?php

namespace Framework\Kernel\View\Blade;

use Framework\Kernel\Contracts\Support\Htmlable;

class ComponentSlot implements Htmlable
{
    protected ?ComponentAttributeBag $attributes = null;

    public function __construct(protected string $contents = '',array $attributes = [])
    {
        $this->withAttributes($attributes);
    }

    public function withAttributes(array $attributes): static
    {
        $this->attributes = new ComponentAttributeBag($attributes);

        return $this;
    }


    public function toHtml(): string
    {
        return $this->contents;
    }
}