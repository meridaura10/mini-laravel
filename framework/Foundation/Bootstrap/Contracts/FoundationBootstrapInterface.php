<?php

namespace Framework\Kernel\Foundation\Bootstrap\Contracts;

use Framework\Kernel\Application\Contracts\ApplicationInterface;

interface FoundationBootstrapInterface
{
    public function bootstrap(ApplicationInterface $app): void;
}
