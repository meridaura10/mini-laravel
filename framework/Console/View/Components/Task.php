<?php

namespace Framework\Kernel\Console\View\Components;

use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\render;
use function Termwind\terminal;

class Task extends Component
{
    public function render(string $description, ?callable $task = null, $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $description = $this->mutate($description, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsureNoPunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $descriptionWidth = mb_strlen(preg_replace("/\<[\w=#\/\;,:.&,%?]+\>|\\e\[\d+m/", '$1', $description) ?? '');

        render("  $description ", false, $verbosity);

        $startTime = microtime(true);

        $result = false;

        try {
            $result = ($task ?: fn () => true)();
        }catch (\Throwable $e){
            $runTime = $task
                ? (' '.number_format((microtime(true) - $startTime) * 1000).'ms')
                : '';

            $runTimeWidth = mb_strlen($runTime);
            $width = min(terminal()->width(), 150);
            $dots = max($width - $descriptionWidth - $runTimeWidth - 10, 0);


            render('<fg=gray>' . str_repeat('░░░░░░', $dots / 6) . '</>', false, $verbosity);
            render("<fg=gray>$runTime</>", false, $verbosity);


            render(
                $result !== false ? ' <fg=green;options=bold>DONE</>' : ' <fg=red;options=bold>FAIL</>',
                $verbosity,
            );

            render($e->getMessage());

        }
    }
}