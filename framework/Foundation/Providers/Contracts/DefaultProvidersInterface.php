<?php

namespace Framework\Kernel\Foundation\Providers\Contracts;

interface DefaultProvidersInterface
{
    public function merge(array $providers): static;

    public function toArray(): array;
}
