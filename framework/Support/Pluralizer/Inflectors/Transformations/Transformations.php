<?php

namespace Framework\Kernel\Support\Pluralizer\Inflectors\Transformations;

use Framework\Kernel\Support\Pluralizer\Inflectors\Contracts\WordInflectorInterface;

class Transformations implements WordInflectorInterface
{
    private array $transformations = [];
    public function __construct(Transformation ...$transformations)
    {
        $this->transformations = $transformations;
    }

    public function inflect(string $word): string
    {
        foreach ($this->transformations as $transformation) {
            if ($transformation->pattern->matches($word)) {
                return $transformation->inflect($word);
            }
        }

        return $word;
    }
}