<?php

namespace Framework\Kernel\Console\View\Components;

use Framework\Kernel\Console\View\Components\Mutators\EnsureDynamicContentIsHighlighted;
use Framework\Kernel\Console\View\Components\Mutators\EnsureNoPunctuation;
use Framework\Kernel\Console\View\Components\Mutators\EnsureRelativePaths;
use Symfony\Component\Console\Output\OutputInterface;

class TwoColumnDetail extends Component
{
    public function render($first, $second = null, $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $first = $this->mutate($first, [
            EnsureDynamicContentIsHighlighted::class,
            EnsureNoPunctuation::class,
            EnsureRelativePaths::class,
        ]);

        $second = $this->mutate($second, [
            EnsureDynamicContentIsHighlighted::class,
            EnsureNoPunctuation::class,
            EnsureRelativePaths::class,
        ]);

        $this->renderView('two-column-detail', [
            'first' => $first,
            'second' => $second,
        ], $verbosity);
    }
}