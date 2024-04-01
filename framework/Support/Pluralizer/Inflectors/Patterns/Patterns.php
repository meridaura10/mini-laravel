<?php

namespace Framework\Kernel\Support\Pluralizer\Inflectors\Patterns;

class Patterns
{
    private $patterns;

    private $regex;

    public function __construct(Pattern ...$patterns)
    {
        $this->patterns = $patterns;

        $patterns = array_map(static function (Pattern $pattern): string {
            return $pattern->getPattern();
        }, $this->patterns);

        $this->regex = '/^(?:' . implode('|', $patterns) . ')$/i';
    }

    public function matches(string $word): bool
    {
        return preg_match($this->regex, $word, $regs) === 1;
    }
}