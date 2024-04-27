<?php

namespace Framework\Kernel\Auth\Contracts;

interface AuthUserProviderInterface
{
    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface;

    public function retrieveById(mixed $identifier): ?AuthenticatableInterface;

    public function retrieveByToken(mixed $identifier,string $token): ?AuthenticatableInterface;

    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool;

    public function updateRememberToken(AuthenticatableInterface $user,string $token): void;
}