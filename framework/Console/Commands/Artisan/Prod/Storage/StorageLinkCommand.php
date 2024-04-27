<?php

namespace Framework\Kernel\Console\Commands\Artisan\Prod\Storage;

use Framework\Kernel\Console\Commands\Command;

class StorageLinkCommand extends Command
{
    protected ?string $signature = 'storage:link
                {--relative : Create the symbolic link using relative paths}
                {--force : Recreate existing symbolic links}';

    protected ?string $description = 'Create the symbolic links configured for the application';

    public function handle(): int
    {
        $relative = $this->option('relative');

        foreach ($this->links() as $link => $target){
            if(file_exists($link) && ! $this->isRemovableSymlink($link,$this->option('force'))){
                $this->view->error("The [$link] link already exists.");
                continue;
            }

            if (is_link($link)) {
                $this->app->make('files')->delete($link);
            }

            if ($relative) {
                $this->app->make('files')->relativeLink($target, $link);
            } else {
                $this->app->make('files')->link($target, $link);
            }

            $this->view->info("The [$link] link has been connected to [$target].");
        }

        return 0;
    }

    protected function isRemovableSymlink(string $link, bool $force): bool
    {
        return is_link($link) && $force;
    }

    protected function links(): array
    {
        return $this->laravel['config']['filesystems.links'] ??
            [app()->publicPath('storage') => app()->storagePath('app/public')];
    }
}