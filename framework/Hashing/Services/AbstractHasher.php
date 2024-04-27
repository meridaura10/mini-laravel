<?php

namespace Framework\Kernel\Hashing\Services;

abstract class AbstractHasher
{
    public function info(string $hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    public function check(string $value,?string $hashedValue, array $options = []): bool
    {
        if (is_null($hashedValue) || strlen($hashedValue) === 0) {
            return false;
        }

        return password_verify($value, $hashedValue);
    }
}