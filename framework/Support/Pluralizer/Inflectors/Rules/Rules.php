<?php

namespace Framework\Kernel\Support\Pluralizer\Inflectors\Rules;

use Framework\Kernel\Support\Pluralizer\Inflectors\Inflectible\Inflectible;
use Framework\Kernel\Support\Pluralizer\Inflectors\Patterns\Patterns;
use Framework\Kernel\Support\Pluralizer\Inflectors\Substitutions\Substitutions;
use Framework\Kernel\Support\Pluralizer\Inflectors\Transformations\Transformations;
use Framework\Kernel\Support\Pluralizer\Inflectors\Uninflected\Uninflected;

class Rules
{
    public static function getSingularRuleset(): Ruleset
    {
        return new Ruleset(
            new Transformations(...Inflectible::getSingular()),
            new Patterns(...Uninflected::getSingular()),
            (new Substitutions(...Inflectible::getIrregular()))->getFlippedSubstitutions()
        );
    }

    public static function getPluralRuleset(): Ruleset
    {
        return new Ruleset(
            new Transformations(...Inflectible::getPlural()),
            new Patterns(...Uninflected::getPlural()),
            new Substitutions(...Inflectible::getIrregular())
        );
    }
}