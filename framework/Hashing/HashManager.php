<?php

namespace Framework\Kernel\Hashing;

use Framework\Kernel\Hashing\Contracts\HasherInterface;
use Framework\Kernel\Hashing\Contracts\HashManagerInterface;
use Framework\Kernel\Hashing\Services\BcryptHasher;
use Framework\Kernel\Support\Manager;

class HashManager extends Manager implements HashManagerInterface, HasherInterface
{
    public function createBcryptDriver(): BcryptHasher
    {
        return new BcryptHasher($this->config->get('hashing.bcrypt') ?? []);
    }

    public function check(string $value, string $hashedValue, array $options = []): bool
    {
        return $this->driver()->check($value, $hashedValue, $options);
    }

    public function make(string $value, array $options = []): string
    {
        return $this->driver()->make($value, $options);
    }

    public function info(string $hashedValue): array
    {
        return $this->driver()->info($hashedValue);
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('hashing.driver', 'bcrypt');
    }
}