<?php

namespace Framework\Kernel\Database\Contracts;

interface ConnectionResolverInterface
{
    public function connection(?string $name = null): ConnectionInterface;

    public function getDefaultConnection(): string;

    public function setDefaultConnection(string $name): void;
}
