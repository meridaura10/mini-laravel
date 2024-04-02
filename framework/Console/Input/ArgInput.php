<?php

namespace Framework\Kernel\Console\Input;

use Exception;

class ArgInput extends Input
{
    protected array $tokens = [];

    protected array $parsed = [];

    public function __construct(?array $argv = null, ?InputDefinition $definition = null)
    {
        $argv ??= $_SERVER['argv'] ?? [];

        // strip the application name
        array_shift($argv);

        $this->tokens = $argv;

        parent::__construct($definition);
    }

    public function getFirstArgument(): ?string
    {
        $isOption = false;

        foreach ($this->tokens as $i => $token) {
            if ($token && $token[0] === '-') {
                if (! str_contains($token, '=') && isset($this->tokens[$i + 1])) {
                    $name = $token[1] === '-' ? substr($token, 2) : substr($token, -1);
                    $isOption = isset($this->options[$name]) && $this->tokens[$i + 1] === $this->options[$name];
                }

                continue;
            }

            if ($isOption) {
                $isOption = false;

                continue;
            }

            return $token;
        }

        return null;
    }

    protected function parse(): void
    {
        $parseOptions = true;
        $this->parsed = $this->tokens;

        while (null !== $token = array_shift($this->parsed)) {
            $parseOptions = $this->parseToken($token, $parseOptions);
        }
    }

    protected function parseToken(string $token, bool $parseOptions): bool
    {

        if ($parseOptions && '' == $token) {
            $this->parseArgument($token);
        } elseif ($parseOptions && '--' == $token) {
            return false;
        } elseif ($parseOptions && str_starts_with($token, '--')) {
            $this->parseLongOption($token);
        } elseif ($parseOptions && '-' === $token[0] && '-' !== $token) {
            $this->parseShortOption($token);
        } else {
            $this->parseArgument($token);
        }

        return $parseOptions;
    }

    private function parseShortOption(string $token): void
    {
        $name = substr($token, 1);

        if (\strlen($name) > 1) {
            if ($this->definition->hasShortcut($name[0]) && $this->definition->getOptionForShortcut($name[0])->acceptValue()) {
                // an option with a value (with no space)
                $this->addShortOption($name[0], substr($name, 1));
            } else {
                $this->parseShortOptionSet($name);
            }
        } else {
            $this->addShortOption($name, null);
        }
    }

    private function parseShortOptionSet(string $name): void
    {
        $len = \strlen($name);
        for ($i = 0; $i < $len; $i++) {
            if (! $this->definition->hasShortcut($name[$i])) {
                $encoding = mb_detect_encoding($name, null, true);
                throw new \Exception(sprintf('The "-%s" option does not exist.', $encoding === false ? $name[$i] : mb_substr($name, $i, 1, $encoding)));
            }

            $option = $this->definition->getOptionForShortcut($name[$i]);
            if ($option->acceptValue()) {
                $this->addLongOption($option->getName(), $i === $len - 1 ? null : substr($name, $i + 1));

                break;
            } else {
                $this->addLongOption($option->getName(), null);
            }
        }
    }

    private function addShortOption(string $shortcut, mixed $value): void
    {
        if (! $this->definition->hasShortcut($shortcut)) {
            throw new Exception(sprintf('The "-%s" option does not exist.', $shortcut));
        }

        $this->addLongOption($this->definition->getOptionForShortcut($shortcut)->getName(), $value);
    }

    private function parseArgument(string $token): void
    {
        $c = \count($this->arguments);

        if ($this->definition->hasArgument($c)) {
            $arg = $this->definition->getArgument($c);
            $this->arguments[$arg->getName()] = $arg->isArray() ? [$token] : $token;

        } elseif ($this->definition->hasArgument($c - 1) && $this->definition->getArgument($c - 1)->isArray()) {
            $arg = $this->definition->getArgument($c - 1);
            $this->arguments[$arg->getName()][] = $token;

        } else {
            $all = $this->definition->getArguments();
            $symfonyCommandName = null;
            if (($inputArgument = $all[$key = array_key_first($all)] ?? null) && $inputArgument->getName() === 'command') {
                $symfonyCommandName = $this->arguments['command'] ?? null;
                unset($all[$key]);
            }

            if (\count($all)) {
                if ($symfonyCommandName) {
                    $message = sprintf('Too many arguments to "%s" command, expected arguments "%s".', $symfonyCommandName, implode('" "', array_keys($all)));
                } else {
                    $message = sprintf('Too many arguments, expected arguments "%s".', implode('" "', array_keys($all)));
                }
            } elseif ($symfonyCommandName) {
                $message = sprintf('No arguments expected for "%s" command, got "%s".', $symfonyCommandName, $token);
            } else {
                $message = sprintf('No arguments expected, got "%s".', $token);
            }

            //            throw new \RuntimeException($message);
        }

    }

    private function parseLongOption(string $token): void
    {
        $name = substr($token, 2);

        if (false !== $pos = strpos($name, '=')) {
            if ('' === $value = substr($name, $pos + 1)) {
                array_unshift($this->parsed, $value);
            }
            $this->addLongOption(substr($name, 0, $pos), $value);
        } else {
            $this->addLongOption($name, null);
        }
    }

    private function addLongOption(string $name, mixed $value): void
    {
        if (! $this->definition->hasOption($name)) {
            if (! $this->definition->hasNegation($name)) {
                throw new Exception(sprintf('The "--%s" option does not exist.', $name));
            }

            $optionName = $this->definition->negationToName($name);
            if ($value !== null) {
                throw new Exception(sprintf('The "--%s" option does not accept a value.', $name));
            }
            $this->options[$optionName] = false;

            return;
        }

        $option = $this->definition->getOption($name);

        if ($value !== null && ! $option->acceptValue()) {
            throw new Exception(sprintf('The "--%s" option does not accept a value.', $name));
        }

        if (\in_array($value, ['', null], true) && $option->acceptValue() && \count($this->parsed)) {
            // if option accepts an optional or mandatory argument
            // let's see if there is one provided
            $next = array_shift($this->parsed);
            if ((isset($next[0]) && $next[0] !== '-') || \in_array($next, ['', null], true)) {
                $value = $next;
            } else {
                array_unshift($this->parsed, $next);
            }
        }

        if ($value === null) {
            if ($option->isValueRequired()) {
                throw new Exception(sprintf('The "--%s" option requires a value.', $name));
            }

            if (! $option->isArray() && ! $option->isValueOptional()) {
                $value = true;
            }
        }

        if ($option->isArray()) {
            $this->options[$name][] = $value;
        } else {
            $this->options[$name] = $value;
        }
    }
}
