<?php

namespace Framework\Kernel\Console\View\Components\Mutators;

class EnsureDynamicContentIsHighlighted
{
    public function __invoke(string $string): string
    {
        return preg_replace('/\[([^\]]+)\]/', '<options=bold>[$1]</>', (string) $string);
    }
}
