<?php

namespace Framework\Kernel\Auth\Contracts;

interface AuthenticatableInterface
{
    public function getAuthPassword(): string;

    public function getAuthIdentifier(): mixed;

    public function getAuthIdentifierName(): string;

    public function getRememberToken(): ?string;

    public function setRememberToken(string $value): void;

    public function getRememberTokenName(): string;
}