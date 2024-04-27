<?php

namespace Framework\Kernel\Auth\Contracts;

use Framework\Kernel\Auth\Contracts\AuthGuardInterface;

interface AuthStatefulGuardInterface extends AuthGuardInterface
{
    public function attempt(array $credentials = [],bool $remember = false): bool;

    public function logout(): void;

    public function guest(): bool;
}