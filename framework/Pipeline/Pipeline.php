<?php

namespace Framework\Kernel\Pipeline;

use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Container\Contracts\ContainerInterface;
use Framework\Kernel\Pipeline\Contracts\PipelineInterface;
use RuntimeException;

class Pipeline implements PipelineInterface
{
    protected string $method = 'handle';

    protected mixed $passable;

    protected array $pipes = [];

    public function __construct(
        protected ?ApplicationInterface $container = null
    ) {

    }

    public function send(mixed $passable): static
    {
        $this->passable = $passable;

        return $this;
    }

    public function through(mixed $pipes): static
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    protected function pipes(): array
    {
        return $this->pipes;
    }

    public function via(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes()),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    protected function carry(): Closure
    {
        return function (callable $stack, mixed $pipe) {
            return function (mixed $passable) use ($stack, $pipe) {
                try {
                    if (is_string($pipe)) {
                        $pipe = $this->getContainer()->make($pipe);
                    }

                    return $pipe->{$this->method}($passable, $stack);
                }catch (\Throwable $e){
                    return $this->handleException($passable, $e);
                }
            };
        };
    }

    protected function prepareDestination(Closure $destination): Closure
    {
        return function (mixed $passable) use ($destination) {
            try {
                return $destination($passable);
            }catch (\Throwable $e){
                return $this->handleException($passable, $e);
            }
        };
    }

    protected function getContainer(): ContainerInterface
    {
        if (! $this->container) {
            throw new RuntimeException('A container instance has not been passed to the Pipeline.');
        }

        return $this->container;
    }

    protected function handleException($passable, \Throwable $e): \Throwable
    {
        throw $e;
    }
}
