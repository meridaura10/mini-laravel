<?php

namespace Framework\Kernel\Pipeline;

use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Container\Contracts\ContainerInterface;
use Framework\Kernel\Pipeline\Contracts\PipelineInterface;

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

                if (is_string($pipe)) {
                    $pipe = new $pipe;
                    //                    $pipe = $this->getContainer()->make($pipe);
                }

                return $pipe->{$this->method}($passable, $stack);
            };
        };
    }

    protected function prepareDestination(Closure $destination): Closure
    {
        return function (mixed $passable) use ($destination) {
            return $destination($passable);
        };
    }

    protected function getContainer(): ContainerInterface
    {
        if (! $this->container) {
            throw new \Exception('A container instance has not been passed to the Pipeline.');
        }

        return $this->container;
    }
}
