<?php

namespace Framework\Kernel\Http\Requests\Support;

class AcceptHeaderItem
{
    private string $value;
    private float $quality = 1.0;
    private int $index = 0;
    private array $attributes = [];


    public function __construct(string $value, array $attributes = [])
    {
        $this->value = $value;
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }


    public function getQuality(): float
    {
        return $this->quality;
    }

    public function getIndex(): int
    {
        return $this->index;
    }


    public function setIndex(int $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function setAttribute(string $name, string $value): static
    {
        if ('q' === $name) {
            $this->quality = (float) $value;
        } else {
            $this->attributes[$name] = $value;
        }

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        $string = $this->value.($this->quality < 1 ? ';q='.$this->quality : '');
        if (\count($this->attributes) > 0) {
            $string .= '; '.HeaderUtils::toString($this->attributes, ';');
        }

        return $string;
    }
}