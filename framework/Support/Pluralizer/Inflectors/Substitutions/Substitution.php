<?php

namespace Framework\Kernel\Support\Pluralizer\Inflectors\Substitutions;

class Substitution
{

    private $from;

    private $to;

    public function __construct(Word $from, Word $to)
    {
        $this->from = $from;
        $this->to   = $to;
    }

    public function getFrom(): Word
    {
        return $this->from;
    }

    public function getTo(): Word
    {
        return $this->to;
    }
}