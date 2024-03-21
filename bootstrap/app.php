<?php

$app = new Framework\Kernel\Application\Application(dirname(__DIR__));

$app->singleton(
    \Framework\Kernel\Http\Contracts\KernelInterface::class,
    \Framework\Kernel\Http\Kernel::class
);

$app->singleton(
    \Framework\Kernel\Console\Contracts\KernelInterface::class,
    \App\Console\Kernel::class
);

return $app;
