<?php

namespace Framework\Kernel\Validator\Rules;

use Closure;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Str;

class ValidationRuleParser
{
    protected array $implicitAttributes = [];

    public function __construct(
        protected array $data,
    )
    {
        //
    }

    public function explode(array $rules): object
    {
        $this->implicitAttributes = [];

        $rules = $this->explodeRules($rules);

        return (object)[
            'rules' => $rules,
            'implicitAttributes' => $this->implicitAttributes,
        ];
    }

    protected function explodeRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            if (str_contains($key, '*')) {
                $rules = $this->explodeWildcardRules($rules, $key, [$rule]);

                unset($rules[$key]);
            } else {
                $rules[$key] = $this->explodeExplicitRule($rule, $key);
            }
        }

        return $rules;
    }

    public static function parse($rule)
    {
//        if ($rule instanceof RuleContract || $rule instanceof NestedRules) {
//            return [$rule, []];
//        }

        if (is_array($rule)) {
            $rule = static::parseArrayRule($rule);
        } else {
            $rule = static::parseStringRule($rule);
        }

        $rule[0] = static::normalizeRule($rule[0]);

        return $rule;
    }

    protected static function normalizeRule(string $rule): string
    {
        return match ($rule) {
            'Int' => 'Integer',
            'Bool' => 'Boolean',
            default => $rule,
        };
    }

    protected static function parseArrayRule(array $rule): array
    {
        return [Str::studly(trim(Arr::get($rule, 0, ''))), array_slice($rule, 1)];
    }

    protected static function parseStringRule(string $rule): array
    {
        $parameters = [];

        if (str_contains($rule, ':')) {
            [$rule, $parameter] = explode(':', $rule, 2);

            $parameters = static::parseParameters($rule, $parameter);
        }

        return [Str::studly(trim($rule)), $parameters];
    }

    protected static function parseParameters(string $rule,string $parameter): array
    {
        return static::ruleIsRegex($rule) ? [$parameter] : str_getcsv($parameter);
    }

    protected static function ruleIsRegex(string $rule): bool
    {
        return in_array(strtolower($rule), ['regex', 'not_regex', 'notregex'], true);
    }

    protected function explodeExplicitRule(array|string $rule, string $attribute): array
    {
        if (is_string($rule)) {
            return explode('|', $rule);
        }

        return array_map(
            [$this, 'prepareRule'],
            $rule,
            array_fill((int) array_key_first($rule), count($rule), $attribute)
        );
    }

    protected function prepareRule(mixed $rule,string $attribute): mixed
    {
       return (string) $rule;
    }

    protected function explodeWildcardRules(array $results, string $attribute, array|string $rules): array
    {
        $pattern = str_replace('\*', '[^\.]*', preg_quote($attribute, '/'));
    }

    public static function filterConditionalRules(array $rules, array $data = []): array
    {
        return collect($rules)->mapWithKeys(function ($attributeRules, $attribute) use ($data) {
            if (! is_array($attributeRules) &&
                ! $attributeRules instanceof ConditionalRules) {
                return [$attribute => $attributeRules];
            }

            if ($attributeRules instanceof ConditionalRules) {
                return [$attribute => $attributeRules->passes($data)
                    ? array_filter($attributeRules->rules($data))
                    : array_filter($attributeRules->defaultRules($data)), ];
            }

            return [$attribute => collect($attributeRules)->map(function ($rule) use ($data) {
                if (! $rule instanceof ConditionalRules) {
                    return [$rule];
                }

                return $rule->passes($data) ? $rule->rules($data) : $rule->defaultRules($data);
            })->filter()->flatten(1)->values()->all()];
        })->all();
    }
}