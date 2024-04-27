<?php

namespace Framework\Kernel\Foundation\Auth\Traits;

trait AuthenticatableTrait
{
    protected string $rememberTokenName = 'remember_token';

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function getAuthIdentifierName(): string
    {
        return $this->getKeyName();
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    public function setRememberToken(string $value): void
    {
        if (! empty($this->getRememberTokenName())) {
            $this->{$this->getRememberTokenName()} = $value;
        }
    }

    public function getRememberToken(): ?string
    {
        if (! empty($this->getRememberTokenName())) {
            return (string) $this->{$this->getRememberTokenName()};
        }
    }

    public function getRememberTokenName(): string
    {
        return $this->rememberTokenName;
    }
}