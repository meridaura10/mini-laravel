<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Foundation\Providers\Contracts\DeferrableProviderInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\Http\Client\ClientHttpFactory;
use Framework\Kernel\Validator\Contracts\UncompromisedVerifierInterface;
use Framework\Kernel\Validator\Contracts\ValidationFactoryInterface;
use Framework\Kernel\Validator\DatabasePresenceVerifier;
use Framework\Kernel\Validator\NotPwnedVerifier;
use Framework\Kernel\Validator\ValidationFactory;

class ValidationServiceProvider extends ServiceProvider implements DeferrableProviderInterface
{
    public function register(): void
    {
        $this->registerPresenceVerifier();
        $this->registerUncompromisedVerifier();
        $this->registerValidationFactory();
    }

    protected function registerPresenceVerifier(): void
    {
        $this->app->singleton('validation.presence', function ($app) {
            return new DatabasePresenceVerifier($app['db']);
        });
    }

    protected function registerValidationFactory(): void
    {
        $this->app->singleton('validator', function (ApplicationInterface $app) {

            $validator = new ValidationFactory($app['translator'], $app);


            if (isset($app['db'], $app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $validator;
        });

        $this->app->alias('validator', ValidationFactoryInterface::class);
    }

    protected function registerUncompromisedVerifier(): void
    {
        $this->app->singleton(UncompromisedVerifierInterface::class, function ($app) {
            return new NotPwnedVerifier($app[ClientHttpFactory::class]);
        });
    }

    public function provides(): array
    {
        return ['validator', 'validation.presence', UncompromisedVerifierInterface::class];
    }
}