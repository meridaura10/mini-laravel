<?php

namespace Framework\Kernel\Console\View\Components\Mutators;

class EnsureRelativePaths
{
    public function __invoke(string $string): string
    {
        if (function_exists('app') && app()->has('path.base')) {
            $string = str_replace(base_path().'/', '', $string);
        }

        return $string;
    }
}
