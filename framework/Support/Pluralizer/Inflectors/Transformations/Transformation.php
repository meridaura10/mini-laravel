<?php

namespace Framework\Kernel\Support\Pluralizer\Inflectors\Transformations;

use Framework\Kernel\Support\Pluralizer\Inflectors\Contracts\WordInflectorInterface;
use Framework\Kernel\Support\Pluralizer\Inflectors\Patterns\Pattern;

readonly class Transformation implements WordInflectorInterface
{
    public function __construct(
        public Pattern $pattern,
        public string  $replacement,
    ){

    }

    public function inflect(string $word): string
    {
        return (string) preg_replace($this->pattern->getRegex(), $this->replacement, $word);
    }
}