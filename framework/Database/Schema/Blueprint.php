<?php

namespace Framework\Kernel\Database\Schema;

use Closure;
use Framework\Kernel\Support\Fluent;

class Blueprint
{
    protected array $commands = [];

    public function __construct(
        protected string $table,
        Closure $callback = null,
    ) {
        if($callback){
            $callback($this);
        }
    }

    protected function createCommand(string $name, array $parameters = []): Fluent
    {
        return new Fluent(array_merge(compact($name), $parameters));
    }

    protected function addCommand(string $name, array $parameters = []): Fluent
    {
        $this->commands[] = $command = $this->createCommand($name,$parameters);



        return $command;
    }

    public function create(): Fluent
    {
        return $this->addCommand('create');
    }
}