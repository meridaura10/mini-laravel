<?php

namespace Framework\Kernel\Support\Pluralizer\Inflectors\Contracts;

interface WordInflectorInterface
{
    public function inflect(string $word): string;
}