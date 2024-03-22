<?php

namespace Framework\Kernel\Console\Commands;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;
use Framework\Kernel\Console\Contracts\InputInterface;
use Framework\Kernel\Console\Parser;
use Framework\Kernel\Console\SCommand;
use Framework\Kernel\Console\Traits\CallsCommandsTrait;
use Framework\Kernel\Console\Traits\HasParametersTrait;
use Framework\Kernel\Console\Traits\InteractsWithIOTrait;
use Framework\Kernel\Console\View\ConsoleView;
use Symfony\Component\String\Exception\ExceptionInterface;

class Command extends SCommand
{
    use HasParametersTrait,
        InteractsWithIOTrait,
        CallsCommandsTrait;

    protected ?ApplicationInterface $app = null;

    protected ?string $signature = null;

    protected ?string $description = null;

    protected ?string $name = null;

    public function __construct()
    {
        if (isset($this->signature)) {
            $this->configureUsingFluentDefinition();
        } else {
            parent::__construct($this->name);
        }

        if (! isset($this->description)) {
            $this->setDescription((string) static::getDefaultDescription());
        } else {
            $this->setDescription((string) $this->description);
        }

        if (! isset($this->signature)) {
            $this->specifyParameters();
        }
    }

    protected function configureUsingFluentDefinition(): void
    {
        [$name, $arguments, $options] = Parser::parse($this->signature);

        parent::__construct($this->name = $name);


        $this->getDefinition()->addArguments($arguments);
        $this->getDefinition()->addOptions($options);

    }

    protected function hadnle(): int
    {
        throw new \Exception('not fount method handle or command');
    }

    protected function execute(InputInterface $input, ConsoleOutputInterface $output): mixed
    {
        return $this->hadnle();
    }

    public function run(InputInterface $input, ConsoleOutputInterface $output): int
    {
        $this->mergeApplicationDefinition();

        $this->initialize($input, $output);

        $this->view = new ConsoleView($output);

        try {
            $input->bind($this->getDefinition());
        } catch (ExceptionInterface $e) {
            dd($e);
        }

        try {
            return $this->execute($input, $output);
        } catch (\Exception $exception) {
            dd('error to run command'.$exception->getMessage());
        }
    }

    protected function initialize(InputInterface $input, ConsoleOutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setApplication(ApplicationInterface $app): void
    {
        $this->app = $app;
    }

    protected function resolveCommand(string|SCommand $command): SCommand
    {
        if (! class_exists($command)) {
           return $this->getArtisan()->find($command);
        }

        $command = $this->app->make($command);

        if ($command instanceof self) {
            $command->setApplication($this->app);
        }

        return $command;
    }
}
