<?php

namespace Framework\Kernel\Console;

use Framework\Kernel\Console\Attribute\AsCommand;
use Framework\Kernel\Console\Contracts\ArtisanInterface;
use Framework\Kernel\Console\Input\InputArgument;
use Framework\Kernel\Console\Input\InputDefinition;
use Framework\Kernel\Console\Input\InputOption;
use ReflectionClass;
use ReflectionProperty;

class SCommand
{
    private ?ArtisanInterface $artisan = null;

    private ?InputDefinition $fullDefinition = null;

    private InputDefinition $definition;

    protected static ?string $defaultName = null;

    protected static ?string $defaultDescription = null;

    private string $description = '';

    private string $name = '';

    public function __construct(string $name)
    {
        $this->definition = new InputDefinition();

        if ($name) {
            $this->setName($name);
        }

        if (! $this->description) {
            $this->setDescription(static::getDefaultDescription() ?? '');
        }

        $this->configure();
    }

    public static function getDefaultName(): ?string
    {
        $class = static::class;

        if ($attribute = (new ReflectionClass($class))->getAttributes(AsCommand::class)) {
            return $attribute[0]->newInstance()->name;
        }

        $r = new ReflectionProperty($class, 'defaultName');

        if ($class !== $r->class || static::$defaultName === null) {
            return null;
        }

        return static::$defaultName;
    }

    public static function getDefaultDescription(): ?string
    {
        $class = static::class;

        if ($attribute = (new ReflectionClass($class))->getAttributes(AsCommand::class)) {
            return $attribute[0]->newInstance()->description;
        }

        $r = new ReflectionProperty($class, 'defaultDescription');

        if ($class !== $r->class || static::$defaultDescription === null) {
            return null;
        }

        return static::$defaultDescription;
    }

    protected function configure()
    {
    }

    public function addArgument(string $name, ?int $mode = null, string $description = '', mixed $default = null /* array|\Closure $suggestedValues = null */): static
    {
        $suggestedValues = \func_num_args() >= 5 ? func_get_arg(4) : [];
        if (! \is_array($suggestedValues) && ! $suggestedValues instanceof \Closure) {
            throw new \TypeError(sprintf('Argument 5 passed to "%s()" must be array or \Closure, "%s" given.', __METHOD__, get_debug_type($suggestedValues)));
        }
        $this->definition->addArgument(new InputArgument($name, $mode, $description, $default, $suggestedValues));
        $this->fullDefinition?->addArgument(new InputArgument($name, $mode, $description, $default, $suggestedValues));

        return $this;
    }

    public function addOption(string $name, string|array|null $shortcut = null, ?int $mode = null, string $description = '', mixed $default = null /* array|\Closure $suggestedValues = [] */): static
    {
        $suggestedValues = \func_num_args() >= 6 ? func_get_arg(5) : [];

        if (! \is_array($suggestedValues) && ! $suggestedValues instanceof \Closure) {
            throw new \TypeError(sprintf('Argument 5 passed to "%s()" must be array or \Closure, "%s" given.', __METHOD__, get_debug_type($suggestedValues)));
        }

        $this->definition->addOption(new InputOption($name, $shortcut, $mode, $description, $default, $suggestedValues));
        $this->fullDefinition?->addOption(new InputOption($name, $shortcut, $mode, $description, $default, $suggestedValues));

        return $this;
    }

    public function mergeApplicationDefinition(bool $mergeArgs = true): void
    {
        if (! $this->artisan) {
            return;
        }

        $this->fullDefinition = new InputDefinition();
        $this->fullDefinition->setOptions($this->definition->getOptions());
        $this->fullDefinition->addOptions($this->artisan->getDefinition()->getOptions());

        if ($mergeArgs) {
            $this->fullDefinition->setArguments($this->artisan->getDefinition()->getArguments());
            $this->fullDefinition->addArguments($this->definition->getArguments());
        } else {
            $this->fullDefinition->setArguments($this->definition->getArguments());
        }
    }

    public function getDefinition(): InputDefinition
    {
        return $this->fullDefinition ?? $this->getNativeDefinition();
    }

    public function getNativeDefinition(): InputDefinition
    {
        return $this->definition ?? throw new \Exception(sprintf('Command class "%s" is not correctly initialized. You probably forgot to call the parent constructor.', static::class));
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getArtisan(): ArtisanInterface
    {
        return $this->artisan;
    }

    public function setArtisan(ArtisanInterface $artisan): void
    {
        $this->artisan = $artisan;

        $this->fullDefinition = null;
    }
}
