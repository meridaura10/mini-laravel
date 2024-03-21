<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Foundation\Providers\AggregateServiceProvider;
use Framework\Kernel\Foundation\Providers\Contracts\DeferrableProviderInterface;
use Framework\Kernel\Foundation\Providers\Services\Commands\ArtisanServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\Commands\ComposerServiceProvider;
use Framework\Kernel\Foundation\Providers\Services\Commands\MigrationServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider implements DeferrableProviderInterface
{
    protected array $providers = [
        ArtisanServiceProvider::class,
        MigrationServiceProvider::class,
        ComposerServiceProvider::class,
    ];
}
