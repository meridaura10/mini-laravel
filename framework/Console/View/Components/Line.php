<?php

namespace Framework\Kernel\Console\View\Components;

use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;

class Line extends Component
{
    protected static array $styles = [
        'info' => [
            'bgColor' => 'blue',
            'fgColor' => 'white',
            'title' => 'info',
        ],
        'warn' => [
            'bgColor' => 'yellow',
            'fgColor' => 'black',
            'title' => 'warn',
        ],
        'error' => [
            'bgColor' => 'red',
            'fgColor' => 'white',
            'title' => 'error',
        ],
    ];

    public function show(string $style, string $content, int $verbosity = ConsoleOutputInterface::VERBOSITY_NORMAL): void
    {
        $content = $this->mutate($content, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsurePunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $this->renderView('line', array_merge(static::$styles[$style], [
            'marginTop' => 1,
            'content' => $content,
        ]), $verbosity);
    }
}
