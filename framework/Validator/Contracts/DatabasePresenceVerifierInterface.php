<?php

namespace Framework\Kernel\Validator\Contracts;

interface DatabasePresenceVerifierInterface extends PresenceVerifierInterface
{
    public function setConnection(string $connection): void;
}