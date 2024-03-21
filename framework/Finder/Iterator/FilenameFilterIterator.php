<?php

namespace Framework\Kernel\Finder\Iterator;

use Framework\Kernel\Finder\Glob;

class FilenameFilterIterator extends MultiplePcreFilterIterator
{
    public function accept(): bool
    {
        return $this->isAccepted($this->current()->getFilename());
    }

    protected function toRegex(string $str): string
    {
        return $this->isRegex($str) ? $str : Glob::toRegex($str);
    }
}
