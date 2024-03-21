<?php

namespace Framework\Kernel\View;

use Framework\Kernel\View\Contracts\FileViewFinderInterface;

class ViewName
{
    public static function normalize(string $name): string
    {
        $delimiter = FileViewFinderInterface::HINT_PATH_DELIMITER;

        if (! str_contains($name, $delimiter)) {
            return str_replace('/', '.', $name);
        }

        [$namespace, $name] = explode($delimiter, $name);

        return $namespace.$delimiter.str_replace('/', '.', $name);
    }
}
