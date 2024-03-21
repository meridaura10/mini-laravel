<?php

namespace Framework\Kernel\EventDispatcher\Contracts;

interface EventDispatcherInterface
{
    public function dispatch(object $event, ?string $eventName = null): object;
}
