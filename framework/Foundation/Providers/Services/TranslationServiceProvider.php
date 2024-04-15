<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Foundation\Providers\Contracts\DeferrableProviderInterface;
use Framework\Kernel\Foundation\Providers\ServiceProvider;
use Framework\Kernel\Translation\FileLoader\FileLoader;
use Framework\Kernel\Translation\Translator;

class TranslationServiceProvider extends ServiceProvider implements DeferrableProviderInterface
{
    public function provides(): array
    {
        // TODO: Implement provides() method.
    }

    public function register(): void
    {
        $this->registerLoader();

        $this->app->singleton('translator', function (ApplicationInterface $app) {
            $loader = $app['translation.loader'];

            $locale = $app->getLocale();

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app->getFallbackLocale());

            return $trans;
        });
    }

    protected function registerLoader(): void
    {
        $this->app->singleton('translation.loader', function (ApplicationInterface $app) {
            return new FileLoader($app['files'], [__DIR__ . '/../../lang', $app['path.lang']]);
        });
    }
}