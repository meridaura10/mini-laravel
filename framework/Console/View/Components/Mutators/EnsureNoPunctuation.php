<?php

namespace Framework\Kernel\Console\View\Components\Mutators;

class EnsureNoPunctuation
{
    public function __invoke(string $string): string
    {
        if (str($string)->endsWith(['.', '?', '!', ':'])) {
            return substr_replace($string, '', -1);
        }

        return $string;
    }
}