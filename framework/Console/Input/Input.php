<?php

namespace Framework\Kernel\Console\Input;

use Framework\Kernel\Console\Contracts\InputInterface;
use Framework\Kernel\Console\Exceptions\InvalidArgumentException;

abstract class Input implements InputInterface
{
    protected array $arguments = [];

    protected array $options = [];

    protected bool $interactive = true;

    protected InputDefinition $definition;

    public function __construct(
        ?InputDefinition $definition = null,
    )
    {
        if ($definition) {
            $this->bind($definition);
            $this->validate();
        } else {
            $this->definition = new InputDefinition();
        }
    }

    public function validate(): void
    {
        $definition = $this->definition;
        $givenArguments = $this->arguments;

        $missingArguments = array_filter(array_keys($definition->getArguments()), fn ($argument) => !\array_key_exists($argument, $givenArguments) && $definition->getArgument($argument)->isRequired());

        if (\count($missingArguments) > 0) {
            throw new \RuntimeException(sprintf('Not enough arguments (missing: "%s").', implode(', ', $missingArguments)));
        }
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(string|int $name): mixed
    {
        if (!$this->definition->hasArgument($name)) {
            throw new InvalidArgumentException(sprintf('The "%s" argument does not exist.', $name));
        }

        return $this->arguments[$name] ?? $this->definition->getArgument($name)->getDefault();
    }


    public function hasArgument(string|int $name): bool
    {
        $arguments = \is_int($name) ? array_values($this->arguments) : $this->arguments;

        return isset($arguments[$name]);
    }

    public function bind(InputDefinition $definition): void
    {
        $this->arguments = [];
        $this->options = [];
        $this->definition = $definition;

        $this->parse();
    }

    public function getOptions(): array
    {
        return array_merge($this->definition->getOptionDefaults(), $this->options);
    }

    public function getOptionDefaults(): array
    {
        $values = [];
        foreach ($this->options as $option) {
            $values[$option->getName()] = $option->getDefault();
        }

        return $values;
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    public function getOption(string $name): mixed
    {
        if ($this->definition->hasNegation($name)) {
            if (null === $value = $this->getOption($this->definition->negationToName($name))) {
                return $value;
            }

            return !$value;
        }

        if (!$this->definition->hasOption($name)) {
            throw new InvalidArgumentException(sprintf('The "%s" option does not exist.', $name));
        }

        return \array_key_exists($name, $this->options) ? $this->options[$name] : $this->definition->getOption($name)->getDefault();
    }

    public function setInteractive(bool $interactive): void
    {
        $this->interactive = $interactive;
    }

    abstract protected function parse(): void;
}
