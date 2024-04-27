<?php

namespace Framework\Kernel\Http\Cookie\Contracts;

interface QueueingFactoryInterface extends CookieFactoryInterface
{
    public function queue(mixed ...$parameters): void;

    public function unqueue(string $name,?string $path = null): void;
}