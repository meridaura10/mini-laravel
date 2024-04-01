<?php

namespace Framework\Kernel\Support\Pluralizer\Inflectors\Rules;

use Framework\Kernel\Support\Pluralizer\Inflectors\Contracts\WordInflectorInterface;

class RulesetInflector implements WordInflectorInterface
{
    private array $rulesets;

    public function __construct(Ruleset $ruleset, Ruleset ...$rulesets)
    {
        $this->rulesets = array_merge([$ruleset], $rulesets);
    }

    public function inflect(string $word): string
    {
        if ($word === '') {
            return '';
        }

        foreach ($this->rulesets as $ruleset) {
            if ($ruleset->uninflected->matches($word)) {
                return $word;
            }

            $inflected = $ruleset->irregular->inflect($word);

            if ($inflected !== $word) {
                return $inflected;
            }

            $inflected = $ruleset->regular->inflect($word);

            if ($inflected !== $word) {
                return $inflected;
            }
        }

        return $word;
    }
}