<?php

namespace Framework\Kernel\View\Contracts;

use Framework\Kernel\Contracts\Support\Htmlable;

interface DeferringDisplayableValueInterface
{
    public function resolveDisplayableValue(): Htmlable|string;
}