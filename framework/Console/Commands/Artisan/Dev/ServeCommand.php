<?php

namespace Framework\Kernel\Console\Commands\Artisan\Dev;

use Framework\Kernel\Console\Commands\Command;

class ServeCommand extends Command
{
    protected ?string $name = 'serve';

    protected ?string $description = 'Serve the application on the PHP development server';

    public function handle(): int
    {
        $environmentFile = base_path('.env');

        $hasEnvironment = file_exists($environmentFile);

        $environmentLastModified = $hasEnvironment
            ? filemtime($environmentFile)
            : now()->addDays(30)->getTimestamp();

        dd('not implemented');
    }

    protected function startProcess(bool $hasEnvironment)
    {

    }

}