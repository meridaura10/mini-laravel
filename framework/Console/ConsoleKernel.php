<?php

namespace Framework\Kernel\Console;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Console\Artisan\Artisan;
use Framework\Kernel\Console\Contracts\ArtisanInterface;
use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;
use Framework\Kernel\Console\Contracts\InputInterface;
use Framework\Kernel\Console\Contracts\KernelInterface;
use Framework\Kernel\Console\Event\ConsoleCommandEvent;
use Framework\Kernel\Console\Event\ConsoleEvents;
use Framework\Kernel\EventDispatcher\Contracts\EventDispatcherInterface;
use Framework\Kernel\EventDispatcher\EventDispatcher;
use Framework\Kernel\Events\Contracts\DispatcherInterface;
use Framework\Kernel\Support\Arr;

class ConsoleKernel implements KernelInterface
{
    protected ?ArtisanInterface $artisan = null;

    protected ?EventDispatcherInterface $symfonyDispatcher = null;

    protected bool $commandsLoaded = false;

    protected array $commands = [];

    protected array $bootstrappers = [
        //        SetRequestForConsole LoadEnvironmentVariables HandleExceptions
        \Framework\Kernel\Foundation\Bootstrap\LoadConfiguration::class,
        \Framework\Kernel\Foundation\Bootstrap\RegisterProviders::class,
        \Framework\Kernel\Foundation\Bootstrap\RegisterFacades::class,
        \Framework\Kernel\Foundation\Bootstrap\BootProviders::class,
    ];

    public function __construct(
        protected ApplicationInterface $app,
        protected DispatcherInterface $events
    ) {
        $this->app->booted(function () {
            $this->rerouteSymfonyCommandEvents();
        });
    }

    public function handle(InputInterface $input, ConsoleOutputInterface $output): int
    {
        $this->bootstrap();

        return $this->getArtisan()->run($input, $output);
    }

    public function getArtisan(): ArtisanInterface
    {
        if (is_null($this->artisan)) {
            $this->artisan = (new Artisan($this->app))
                ->resolveCommands($this->commands)
                ->setContainerCommandLoader()
                ->setEventDispatcher($this->symfonyDispatcher);
        }

        return $this->artisan;
    }

    public function rerouteSymfonyCommandEvents(): static
    {
        if (is_null($this->symfonyDispatcher)) {
            $this->symfonyDispatcher = new EventDispatcher();

            //            $this->symfonyDispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
            //                $this->events->dispatch(
            //                    new CommandStarting($event->command->getName(), $event->input, $event->output)
            //                );
            //            });
            //
            //            $this->symfonyDispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) {
            //                $this->events->dispatch(
            //                    new CommandFinished($event->getCommand()->getName(), $event->getInput(), $event->getOutput(), $event->getExitCode())
            //                );
            //            });
        }

        return $this;
    }

    protected function commands(): void
    {
    }

    protected function load(array|string $paths): void
    {
        $paths = array_unique(Arr::wrap($paths));

        $paths = array_filter($paths, function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }
    }

    public function bootstrap(): void
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }

        $this->app->loadDeferredProviders();

        if (! $this->commandsLoaded) {
            $this->commands();

            $this->commandsLoaded = true;
        }
    }

    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }
}
