<?php

namespace Framework\Kernel\Hashing\Services;

use Framework\Kernel\Hashing\Contracts\HasherInterface;
use RuntimeException;

class BcryptHasher extends AbstractHasher implements HasherInterface
{
    protected bool $verifyAlgorithm = false;

    protected int $rounds = 12;

    public function __construct(array $options = [])
    {
        $this->rounds = $options['rounds'] ?? $this->rounds;
        $this->verifyAlgorithm = $options['verify'] ?? $this->verifyAlgorithm;
    }

    public function check(string $value, ?string $hashedValue, array $options = []): bool
    {
        if ($this->verifyAlgorithm && ! $this->isUsingCorrectAlgorithm($hashedValue)) {
            throw new RuntimeException('This password does not use the Bcrypt algorithm.');
        }

        return parent::check($value, $hashedValue, $options);
    }

    protected function isUsingCorrectAlgorithm(string $hashedValue): bool
    {
        return $this->info($hashedValue)['algoName'] === 'bcrypt';
    }

    public function make(string $value, array $options = []): string
    {
        $hash = password_hash($value, PASSWORD_BCRYPT, [
            'cost' => $this->cost($options),
        ]);

        if ($hash === false) {
            throw new RuntimeException('Bcrypt hashing not supported.');
        }

        return $hash;
    }

    protected function cost(array $options = []): int
    {
        return $options['rounds'] ?? $this->rounds;
    }
}