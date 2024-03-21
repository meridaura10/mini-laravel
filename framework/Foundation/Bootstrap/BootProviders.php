<?php

namespace Framework\Kernel\Foundation\Bootstrap;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Foundation\Bootstrap\Contracts\FoundationBootstrapInterface;

class BootProviders implements FoundationBootstrapInterface
{
    public function bootstrap(ApplicationInterface $app): void
    {
        $app->boot();
    }
}
