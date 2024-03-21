<?php

namespace Framework\Kernel\Events;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Events\Contracts\DispatcherInterface;

class Dispatcher implements DispatcherInterface
{
    public function __construct(
        protected ApplicationInterface $app,
    )
    {

    }
}
