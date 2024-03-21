<?php

namespace Framework\Kernel\Console\Commands\Database\Migrations;


use Framework\Kernel\Console\Commands\BaseCommand;
use Framework\Kernel\Console\Input\InputOption;
use Framework\Kernel\Database\Migrations\Contracts\MigrationRepositoryInterface;

class MigrateInstallCommand extends BaseCommand
{

    protected ?string $name = 'migrate:install';

    protected ?string $description = 'Create the migration repository';

    public function __construct(
        protected MigrationRepositoryInterface $repository,
    ) {
        parent::__construct();
    }

    public function hadnle(): int
    {
        $this->repository->setSource($this->input->getOption('database'));

        $this->repository->createRepository();

        $this->view->info('Migration table created successfully.');
    }

    protected function getOptions(): array
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
        ];
    }
}