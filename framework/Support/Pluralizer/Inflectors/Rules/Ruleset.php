<?php

namespace Framework\Kernel\Support\Pluralizer\Inflectors\Rules;

use Framework\Kernel\Support\Pluralizer\Inflectors\Patterns\Patterns;
use Framework\Kernel\Support\Pluralizer\Inflectors\Substitutions\Substitutions;
use Framework\Kernel\Support\Pluralizer\Inflectors\Transformations\Transformations;

readonly class Ruleset
{
    public function __construct(
        public Transformations $regular,
        public Patterns        $uninflected,
        public Substitutions   $irregular
    )
    {

    }
}