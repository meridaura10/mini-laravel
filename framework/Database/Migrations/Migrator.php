<?php

namespace Framework\Kernel\Database\Migrations;

use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;
use Framework\Kernel\Console\View\Components\Info;
use Framework\Kernel\Console\View\Components\Task;
use Framework\Kernel\Database\Contracts\ConnectionResolverInterface;
use Framework\Kernel\Database\Migrations\Contracts\MigrationRepositoryInterface;
use Framework\Kernel\Events\Contracts\DispatcherInterface;
use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;
use function Termwind\render;

class Migrator
{
    protected static array $requiredPathCache = [];
    protected array $paths = [];

    protected ?string $connection = null;

    protected ?ConsoleOutputInterface $output = null;

    public function __construct(
        protected MigrationRepositoryInterface $repository,
        protected ConnectionResolverInterface  $resolver,
        protected FilesystemInterface          $files,
        protected DispatcherInterface          $events,
    )
    {

    }

    public function usingConnection(?string $name, callable $callback): mixed
    {
        $previousConnection = $this->resolver->getDefaultConnection();

        $this->setConnection($name);

        return tap($callback(), function () use ($previousConnection) {
            $this->setConnection($previousConnection);
        });
    }

    public function run(array|string $paths, array $options): array
    {
        $files = $this->getMigrationFiles($paths);

        $this->requireFiles($migrations = $this->pendingMigrations(
            $files, $this->repository->getRan(),
        ));

        $this->runPending($migrations, $options);

        return $migrations;
    }

    public function runPending(array $migrations, array $options = []): void
    {
        if(count($migrations) === 0){
            $this->write(Info::class, 'Nothing to migrate');

            return;
        }

        $batch = $this->repository->getNextBatchNumber();

        $pretend = $options['pretend'] ?? false;

        $step = $options['step'] ?? false;

        $this->write(Info::class, 'Running migrations.');

        foreach ($migrations as $file) {
            $this->runUp($file, $batch, $pretend);

            if ($step) {
                $batch++;
            }
        }

        $this->write(Info::class, 'Finish saving migrations.');
    }

    protected function write(string $component,...$arguments): void
    {
        if ($this->output && class_exists($component)) {
            (new $component($this->output))->render(...$arguments);
        } else {
            foreach ($arguments as $argument) {
                if (is_callable($argument)) {
                    $argument();
                }
            }
        }
    }

    protected function runUp(string $file, int $batch, bool $pretend): void
    {
        $migration = $this->resolvePath($file);

        $name = $this->getMigrationName($file);

//        if ($pretend) {
//            return $this->pretendToRun($migration, 'up');
//        }

        $this->write(Task::class, $name, fn () => $this->runMigration($migration, 'up'));

        $this->repository->log($name, $batch);
    }

    protected function runMigration(Migration $migration, string $method)
    {

    }

    protected function resolvePath(string $path): Migration
    {
        return static::$requiredPathCache[$path] ??= $this->files->getRequire($path);
    }

    public function requireFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->files->requireOnce($file);
        }
    }

    protected function pendingMigrations(array $files, array $ran): array
    {
        return collect($files)
            ->reject(function ($file) use ($ran) {
                return in_array($this->getMigrationName($file), $ran);
            })->values()->all();
    }

    public function getMigrationFiles(array|string $paths): array
    {
        return collect($paths)
            ->flatMap(function (string $path) {
                return str_ends_with($path, '.php') ? [$path] : $this->files->glob($path . '/*_*.php');
            })->keyBy(function (string $file) {
                return $this->getMigrationName($file);
            })->sortBy(function ($file, $key) {
                return $key;
            })->all();
    }

    public function getMigrationName(string $path): string
    {
        return str_replace('.php', '', basename($path));
    }

    public function setConnection(?string $name): void
    {
        if ($name) {
            $this->resolver->setDefaultConnection($name);
        }

        $this->repository->setSource($name);

        $this->connection = $name;
    }

    public function repositoryExists(): bool
    {
        return $this->repository->repositoryExists();
    }

    public function setOutput(ConsoleOutputInterface $output): static
    {
        $this->output = $output;

        return $this;
    }

    public function paths(): array
    {
        return $this->paths;
    }
}