<?php

namespace Framework\Kernel\Validator\Traits;

trait ReplacesAttributesTrait
{
    protected function replaceMin(string $message,string $attribute,string $rule,array $parameters): string
    {
        return str_replace(':min', $parameters[0], $message);
    }
}