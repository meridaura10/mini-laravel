<?php

namespace Framework\Kernel\Console\Traits;

use Framework\Kernel\Console\Contracts\InputInterface;
use Framework\Kernel\Console\View\Contracts\ConsoleViewInterface;

trait InteractsWithIOTrait
{
    protected ?ConsoleViewInterface $view = null;

    protected ?InputInterface $input = null;

    public function argument(?string $key = null): mixed
    {
        if (is_null($key)) {
            return $this->input->getArguments();
        }

        return $this->input->getArgument($key);
    }

    public function option($key = null)
    {
        if (is_null($key)) {
            return $this->input->getOptions();
        }

        return $this->input->getOption($key);
    }

    public function hasOption(string $name): bool
    {
        return $this->input->hasOption($name);
    }
}
