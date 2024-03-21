<?php

namespace Framework\Kernel\Console\Artisan;

use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Console\CommandLoader;
use Framework\Kernel\Console\Commands\Command;
use Framework\Kernel\Console\Contracts\ArtisanInterface;
use Framework\Kernel\Console\Contracts\CommandLoaderInterface;
use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;
use Framework\Kernel\Console\Contracts\InputInterface;
use Framework\Kernel\Console\Event\ConsoleCommandEvent;
use Framework\Kernel\Console\Event\ConsoleEvents;
use Framework\Kernel\Console\Exceptions\CommandNotFoundException;
use Framework\Kernel\Console\GeneratorCommand;
use Framework\Kernel\Console\Input\InputArgument;
use Framework\Kernel\Console\Input\InputDefinition;
use Framework\Kernel\Console\Input\InputOption;
use Framework\Kernel\EventDispatcher\Contracts\EventDispatcherInterface;

class Artisan implements ArtisanInterface
{
    protected ?EventDispatcherInterface $dispatcher = null;

    protected ?CommandLoaderInterface $commandLoader = null;

    private ?InputDefinition $fullDefinition = null;

    protected array $commands = [];

    private array $commandMap = [];

    protected static array $bootstrappers = [];

    public function __construct(
        protected ApplicationInterface $app
    ) {
        $this->bootstrap();
    }

    public function run(InputInterface $input, ConsoleOutputInterface $output): int
    {
        try {
            $exitCode = $this->doRun($input, $output);

            return $exitCode;
        } catch (\Exception $exception) {
            dd($exception->getMessage().' error to run command '.$input->getFirstArgument(), $exception->getMessage());
        }
    }

    protected function doRun(InputInterface $input, ConsoleOutputInterface $output): int
    {
        $commandName = $input->getFirstArgument();

        try {
            $input->bind($this->getDefinition());
        } catch (\Exception $exception) {

        }

        $command = $this->find($commandName);

        return $this->doRunCommand($command, $input, $output);
    }

    public function getDefinition(): InputDefinition
    {
        return $this->getDefaultInputDefinition();
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display help for the given command. When no command is given display help for the <info></info> command'),
            new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
            new InputOption('--ansi', '', InputOption::VALUE_NEGATABLE, 'Force (or disable --no-ansi) ANSI output', null),
            new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
        ]);
    }

    protected function doRunCommand(Command $command, InputInterface $input, ConsoleOutputInterface $output): int
    {
        $event = new ConsoleCommandEvent($command, $input, $output);

        $command->mergeApplicationDefinition();

        try {
            $input->bind($command->getDefinition());
        } catch (\Exception $exception) {

        }

        $this->dispatcher->dispatch($event, ConsoleEvents::COMMAND);

        return $command->run($input, $output);
    }

    public function find(string $name): Command
    {
        return $this->get($name);
    }

    public function get(string $name): ?Command
    {
        if (! $this->has($name)) {
            throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $name));
        }

        if (! isset($this->commands[$name])) {
            throw new CommandNotFoundException(sprintf('The "%s" command cannot be found because it is registered under multiple names. Make sure you don\'t set a different name via constructor or "setName()".', $name));
        }

        return $this->commands[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->commands[$name]) || ($this->commandLoader?->has($name) && $this->add($this->commandLoader->get($name)));
    }

    public function resolveCommands(mixed $commands): static
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        foreach ($commands as $command) {
            $this->resolve($command);
        }

        return $this;
    }

    public static function starting(Closure $callback): void
    {
        static::$bootstrappers[] = $callback;
    }

    protected function bootstrap(): void
    {
        foreach (static::$bootstrappers as $bootstrapper) {
            $bootstrapper($this);
        }
    }

    public function resolve(Command|string $command): ?Command
    {
        if (is_subclass_of($command, GeneratorCommand::class) && ($commandName = $command::getDefaultName())) {
            $this->commandMap[$commandName] = $command;

            return null;
        }

        if ($command instanceof Command) {
            return $this->add($command);
        }

        return $this->add($this->app->make($command));
    }

    public function add(Command $command): Command
    {
        $command->setArtisan($this);
        $command->setApplication($this->app);

        return $this->setCommand($command);
    }

    public function setCommand(Command $command): Command
    {
        $command->setArtisan($this);

        if (! $command->getName()) {
            throw new \LogicException(sprintf('The command defined in "%s" cannot have an empty name.', get_debug_type($command)));
        }

        $this->commands[$command->getName()] = $command;

        return $command;
    }

    public function setContainerCommandLoader(): static
    {
        $this->setCommandLoader(new CommandLoader($this->app, $this->commandMap));

        return $this;
    }

    public function setCommandLoader(CommandLoaderInterface $commandLoader): void
    {
        $this->commandLoader = $commandLoader;
    }

    public function setEventDispatcher(EventDispatcherInterface $dispatcher): static
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }
}
