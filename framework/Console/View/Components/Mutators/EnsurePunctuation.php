<?php

namespace Framework\Kernel\Console\View\Components\Mutators;

class EnsurePunctuation
{
    public function __invoke(string $string): string
    {
        if (! str($string)->endsWith(['.', '?', '!', ':'])) {
            return "$string.";
        }

        return $string;
    }
}
