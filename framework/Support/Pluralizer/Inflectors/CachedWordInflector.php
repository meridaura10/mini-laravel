<?php

namespace Framework\Kernel\Support\Pluralizer\Inflectors;

use Framework\Kernel\Support\Pluralizer\Inflectors\Contracts\WordInflectorInterface;

class CachedWordInflector implements WordInflectorInterface
{
    private array $cache = [];

    public function __construct(private WordInflectorInterface $wordInflector)
    {
    }

    public function inflect(string $word): string
    {
        return $this->cache[$word] ?? $this->cache[$word] = $this->wordInflector->inflect($word);
    }
}