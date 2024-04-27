<?php

namespace Framework\Kernel\Auth\Contracts;

interface AuthGuardInterface
{
    public function user(): ?AuthenticatableInterface;

    public function check(): bool;
}