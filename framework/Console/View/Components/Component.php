<?php

namespace Framework\Kernel\Console\View\Components;

use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;

use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\render;
use function Termwind\renderUsing;

abstract class Component
{
    public function __construct(
        protected OutputInterface $output,
    ) {

    }

    protected function renderView(string $view, array $data, int $verbosity): void
    {
//        renderUsing($this->output);

        render((string) $this->compile($view, $data), $verbosity);
    }

    protected function compile(string $view, array $data)
    {
        extract($data);

        ob_start();

        include __DIR__."/../../resources/views/components/$view.php";

        return tap(ob_get_contents(), function () {
            ob_end_clean();
        });
    }

    protected function mutate(mixed $data, iterable $mutators): mixed
    {
        foreach ($mutators as $mutator) {
            $mutator = new $mutator;

            if (is_iterable($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = $mutator($value);
                }
            } else {
                $data = $mutator($data);
            }
        }

        return $data;
    }
}
