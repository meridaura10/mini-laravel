<?php

namespace Framework\Kernel\Console\Traits;

use Framework\Kernel\Console\Input\InputArgument;
use Framework\Kernel\Console\Input\InputOption;

trait HasParametersTrait
{
    protected function specifyParameters(): void
    {
        foreach ($this->getArguments() as $arguments) {
            if ($arguments instanceof InputArgument) {
                $this->getDefinition()->addArgument($arguments);
            } else {
                $this->addArgument(...$arguments);
            }
        }

        foreach ($this->getOptions() as $options) {
            if ($options instanceof InputOption) {
                $this->getDefinition()->addOption($options);
            } else {
                $this->addOption(...$options);
            }
        }
    }

    protected function getArguments(): array
    {
        return [];
    }

    protected function getOptions(): array
    {
        return [];
    }
}
