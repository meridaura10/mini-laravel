<?php

namespace Framework\Kernel\Foundation\Bootstrap;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Facades\Facade;
use Framework\Kernel\Foundation\Bootstrap\Contracts\FoundationBootstrapInterface;

class RegisterFacades implements FoundationBootstrapInterface
{
    public function bootstrap(ApplicationInterface $app): void
    {
        Facade::setFacadeApplication($app);
    }
}
