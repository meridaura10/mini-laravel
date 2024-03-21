<?php

namespace Framework\Kernel\Foundation\Providers;

use Framework\Kernel\Foundation\Providers\Services\FormRequestServiceProvider;

class FoundationServiceProvider extends AggregateServiceProvider
{
    protected array $providers = [
        FormRequestServiceProvider::class,
    ];
}
