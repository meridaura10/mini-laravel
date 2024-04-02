<?php

namespace Framework\Kernel\Console\Commands\Database\Seeders;

use Framework\Kernel\Console\Commands\Command;
use Framework\Kernel\Console\Input\InputArgument;
use Framework\Kernel\Console\Input\InputOption;
use Framework\Kernel\Database\Contracts\ConnectionResolverInterface;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Seeders\Seeder;

class SeedCommand extends Command
{
    protected ?string $name = 'db:seed';

    protected ?string $description = 'Seed the database with records';

    public function __construct(
        protected ConnectionResolverInterface $resolver,
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->view->info('Seeding database.');


        $previousConnection = $this->resolver->getDefaultConnection();

        $this->resolver->setDefaultConnection($this->getDatabase());

        Model::unguarded(function () {
            $this->getSeeder()->__invoke();
        });

        if ($previousConnection) {
            $this->resolver->setDefaultConnection($previousConnection);
        }

        return 0;
    }

    protected function getSeeder(): Seeder
    {
        $class = $this->argument('class') ?? $this->option('class');

        if (! str_contains($class, '\\')) {
            $class = 'Database\\Seeders\\'.$class;
        }

        if ($class === 'Database\\Seeders\\DatabaseSeeder' &&
            ! class_exists($class)) {
            $class = 'DatabaseSeeder';
        }

        return $this->app->make($class)
            ->setContainer($this->app)
            ->setCommand($this);
    }

    protected function getDatabase(): string
    {
        $database = $this->input->getOption('database');

        return $database ?: $this->app['config']['database.default'];
    }

    protected function getArguments(): array
    {
        return [
            ['class', InputArgument::OPTIONAL, 'The class name of the root seeder', null],
        ];
    }


    protected function getOptions(): array
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'Database\\Seeders\\DatabaseSeeder'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}