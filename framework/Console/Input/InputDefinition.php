<?php

namespace Framework\Kernel\Console\Input;

use Framework\Kernel\Console\Exceptions\InvalidArgumentException;
use Framework\Kernel\Console\Exceptions\LogicException;

class InputDefinition
{
    private array $arguments = [];

    private array $options = [];

    private array $shortcuts = [];

    private array $negations = [];

    public function __construct(array $definition = [])
    {
        $this->setDefinition($definition);
    }

    public function setDefinition(array $definition): void
    {
        $arguments = [];
        $options = [];

        foreach ($definition as $item) {
            if ($item instanceof InputOption) {
                $options[] = $item;
            } else {
                $arguments[] = $item;
            }
        }

        $this->setArguments($arguments);
        $this->setOptions($options);
    }

    public function hasArgument(string|int $name): bool
    {
        $arguments = \is_int($name) ? array_values($this->arguments) : $this->arguments;

        return isset($arguments[$name]);
    }

    public function setArguments(array $arguments = []): void
    {
        $this->arguments = [];
        $this->addArguments($arguments);
    }

    public function addArguments(?array $arguments = []): void
    {
        if ($arguments !== null) {
            foreach ($arguments as $argument) {
                $this->addArgument($argument);
            }
        }
    }

    public function addArgument(InputArgument $argument): void
    {
        $this->arguments[$argument->getName()] = $argument;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(string|int $name): InputArgument
    {
        if (! $this->hasArgument($name)) {
            throw new InvalidArgumentException(sprintf('The "%s" argument does not exist.', $name));
        }

        $arguments = \is_int($name) ? array_values($this->arguments) : $this->arguments;

        return $arguments[$name];
    }



    public function setOptions(array $options = []): void
    {
        $this->options = [];
        $this->shortcuts = [];
        $this->negations = [];
        $this->addOptions($options);
    }

    public function addOptions(array $options = []): void
    {
        foreach ($options as $option) {
            $this->addOption($option);
        }
    }

    public function addOption(InputOption $option): void
    {
        if (isset($this->options[$option->getName()]) && ! $option->equals($this->options[$option->getName()])) {
            throw new LogicException(sprintf('An option named "%s" already exists.', $option->getName()));
        }
        if (isset($this->negations[$option->getName()])) {
            throw new LogicException(sprintf('An option named "%s" already exists.', $option->getName()));
        }

        if ($option->getShortcut()) {
            foreach (explode('|', $option->getShortcut()) as $shortcut) {
                if (isset($this->shortcuts[$shortcut]) && ! $option->equals($this->options[$this->shortcuts[$shortcut]])) {
                    throw new LogicException(sprintf('An option with shortcut "%s" already exists.', $shortcut));
                }
            }
        }

        $this->options[$option->getName()] = $option;
        if ($option->getShortcut()) {
            foreach (explode('|', $option->getShortcut()) as $shortcut) {
                $this->shortcuts[$shortcut] = $option->getName();
            }
        }

        if ($option->isNegatable()) {
            $negatedName = 'no-'.$option->getName();
            if (isset($this->options[$negatedName])) {
                throw new LogicException(sprintf('An option named "%s" already exists.', $negatedName));
            }
            $this->negations[$negatedName] = $option->getName();
        }
    }

    public function getOptionDefaults(): array
    {
        $values = [];
        foreach ($this->options as $option) {
            $values[$option->getName()] = $option->getDefault();
        }

        return $values;
    }

    public function hasNegation(string $name): bool
    {
        return isset($this->negations[$name]);
    }

    public function negationToName(string $negation): string
    {
        if (! isset($this->negations[$negation])) {
            throw new InvalidArgumentException(sprintf('The "--%s" option does not exist.', $negation));
        }

        return $this->negations[$negation];
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    public function hasShortcut(string $name): bool
    {
        return isset($this->shortcuts[$name]);
    }

    public function getOptionForShortcut(string $shortcut): InputOption
    {
        return $this->getOption($this->shortcutToName($shortcut));
    }

    public function shortcutToName(string $shortcut): string
    {
        if (! isset($this->shortcuts[$shortcut])) {
            throw new InvalidArgumentException(sprintf('The "-%s" option does not exist.', $shortcut));
        }

        return $this->shortcuts[$shortcut];
    }

    public function getOption(string $name): InputOption
    {
        if (! $this->hasOption($name)) {
            throw new InvalidArgumentException(sprintf('The "--%s" option does not exist.', $name));
        }

        return $this->options[$name];
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
