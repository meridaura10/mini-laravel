<?php

namespace Framework\Kernel\Support\Pluralizer;

use Framework\Kernel\Support\Pluralizer\Inflectors\CachedWordInflector;
use Framework\Kernel\Support\Pluralizer\Inflectors\Inflector;
use Framework\Kernel\Support\Pluralizer\Inflectors\InflectorFactory;
use Framework\Kernel\Support\Pluralizer\Inflectors\Rules\Rules;
use Framework\Kernel\Support\Pluralizer\Inflectors\Rules\RulesetInflector;

class Pluralizer
{
    protected static ?Inflector $inflector = null;

    public static array $uncountable = [
        'recommended',
        'related',
    ];

    public static function plural(string $value,int|array|\Countable $count = 2): string
    {
        if (is_countable($count)) {
            $count = count($count);
        }

        if ((int) abs($count) === 1 || static::uncountable($value) || preg_match('/^(.*)[A-Za-z0-9\x{0080}-\x{FFFF}]$/u', $value) == 0) {
            return $value;
        }

        $plural = static::inflector()->pluralize($value);

        return static::matchCase($plural, $value);
    }

    protected static function matchCase($value, $comparison): string
    {
        $functions = ['mb_strtolower', 'mb_strtoupper', 'ucfirst', 'ucwords'];

        foreach ($functions as $function) {
            if ($function($comparison) === $comparison) {
                return $function($value);
            }
        }

        return $value;
    }

    public static function inflector(): Inflector
    {
        if (is_null(static::$inflector)) {
            static::$inflector = new Inflector(
                new CachedWordInflector(new RulesetInflector(
                    Rules::getSingularRuleset(),
                )),
                new CachedWordInflector(new RulesetInflector(
                    Rules::getPluralRuleset(),
                ))
            );
        }

        return static::$inflector;
    }

    protected static function uncountable(string $value): bool
    {
        return in_array(strtolower($value), static::$uncountable);
    }
}