<?php

namespace Framework\Kernel\Database\Seeders;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Console\Commands\Command;
use Framework\Kernel\Console\View\Components\TwoColumnDetail;
use Framework\Kernel\Support\Arr;
use function Termwind\render;

abstract class Seeder
{
    protected ?ApplicationInterface $container = null;

    protected ?Command $command = null;

    protected static array $called = [];

    public function setContainer(ApplicationInterface $container): static
    {
        $this->container = $container;

        return $this;
    }

    public function setCommand(Command $command): static
    {
        $this->command = $command;

        return $this;
    }

    public function call(array|string $class, bool $silent = false, array $parameters = []): static
    {
        $classes = Arr::wrap($class);

        foreach ($classes as $class){
            $seeder = $this->resolve($class);

            $name = get_class($seeder);

            if ($silent === false && isset($this->command)) {
                with(new TwoColumnDetail($this->command->getOutput()))->render(
                    $name,
                    '<fg=yellow;options=bold>RUNNING</>'
                );
            }

            $startTime = microtime(true);

            $seeder->__invoke($parameters);

            if ($silent === false && isset($this->command)) {
                $runTime = number_format((microtime(true) - $startTime) * 1000);

                with(new TwoColumnDetail($this->command->getOutput()))->render(
                    $name,
                    "<fg=gray>$runTime ms</> <fg=green;options=bold>DONE</>"
                );

                render('');
            }

            static::$called[] = $class;
        }

        return $this;
    }

    protected function resolve(string $class): Seeder
    {
        if(isset($this->container)){
            $instance = $this->container->make($class);

            $instance->setContainer($this->container);
        }else{
            $instance = new $class;
        }

        if (isset($this->command)) {
            $instance->setCommand($this->command);
        }

        return $instance;
    }

    public function __invoke(array $parameters = []): mixed
    {
        $callback = fn () => isset($this->container)
            ? $this->container->call([$this, 'run'], $parameters)
            : $this->run(...$parameters);


        return $callback();
    }

    abstract public function run();
}