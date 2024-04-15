<?php

namespace Framework\Kernel\Translation\Contracts;

interface TranslatorInterface
{
    public function get(string $key, array $replace = [],?string $locale = null, bool $fallback = false): array|string;
}