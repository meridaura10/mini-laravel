<?php

namespace Framework\Kernel\Database\Migrations;

use Framework\Kernel\Console\Contracts\ConsoleOutputInterface;
use Framework\Kernel\Console\View\Components\Info;
use Framework\Kernel\Console\View\Components\Task;
use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Contracts\ConnectionResolverInterface;
use Framework\Kernel\Database\Migrations\Contracts\MigrationRepositoryInterface;
use Framework\Kernel\Database\Schema\Grammar\SchemaGrammar;
use Framework\Kernel\Events\Contracts\DispatcherInterface;
use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;
use Framework\Kernel\Support\Arr;
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

    public function reset(array $paths = [], $pretend = false): array
    {
        $migrations = array_reverse($this->repository->getRan());

        if (count($migrations) === 0) {
            $this->write(Info::class, 'Nothing to rollback.');

            return [];
        }

        return tap($this->resetMigrations($migrations, Arr::wrap($paths), $pretend), function () {
            $this->output->writeln('');
        });
    }

    protected function resetMigrations(array $migrations, array $paths, bool $pretend = false): array
    {

        $migrations = collect($migrations)->map(function (string $m){
            return (object) ['migration' => $m];
        })->all();

        return $this->rollbackMigrations(
            $migrations, $paths, compact('pretend'),
        );
    }

    protected function rollbackMigrations(array $migrations, array $paths, array $options): array
    {
        $rolledBack = [];

        $this->requireFiles($files = $this->getMigrationFiles($paths));

        $this->write(Info::class, 'Rolling back migrations.');


        foreach ($migrations as $migration){
            $migration = (object) $migration;

            if (! $file = Arr::get($files, $migration->migration)) {
//                $this->write(::class, $migration->migration, '<fg=yellow;options=bold>Migration not found</>');

                continue;
            }

            $rolledBack[] = $file;

            $this->runDown(
                $file, $migration,
                $options['pretend'] ?? false
            );
        }

        return $rolledBack;
    }

    protected function runDown(string $file, object $migration, bool $pretend = false): void
    {

        $instance = $this->resolvePath($file);

        $name = $this->getMigrationName($file);

//        if ($pretend) {
//            return $this->pretendToRun($instance, 'down');
//        }

        $this->write(Task::class, $name, fn () => $this->runMigration($instance, 'down'));

        $this->repository->delete($migration);
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
        if (count($migrations) === 0) {
            $this->write(Info::class, 'Nothing to migrate');

            return;
        }

        $batch = $this->repository->getNextBatchNumber();

        $step = $options['step'] ?? false;

        $this->write(Info::class, 'Running migrations.');

        foreach ($migrations as $file) {
            $this->runUp($file, $batch);

            if ($step) {
                $batch++;
            }
        }
    }

    protected function write(string $component, ...$arguments): void
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

    protected function runUp(string $file, int $batch): void
    {
        $migration = $this->resolvePath($file);

        $name = $this->getMigrationName($file);

        $this->write(Task::class, $name, fn() => $this->runMigration($migration, 'up'));

        $this->repository->log($name, $batch);
    }

    protected function runMigration(Migration $migration, string $method): void
    {
        $connection = $this->resolveConnection(
            $migration->getConnection(),
        );

        $callback = function () use ($connection, $migration, $method) {
            if (method_exists($migration, $method)) {
                $this->runMethod($connection, $migration, $method);
            }
        };

        $this->getSchemaGrammar($connection)->supportsSchemaTransactions()
        && $migration->withinTransaction
            ? $connection->transaction($callback)
            : $callback();
    }

    protected function runMethod(ConnectionInterface $connection, Migration $migration, string $method): void
    {
        $previousConnection = $this->resolver->getDefaultConnection();


        try {
            $this->resolver->setDefaultConnection($connection->getName());

            $migration->{$method}();
        } finally {
            $this->resolver->setDefaultConnection($previousConnection);
        }
    }

    protected function getSchemaGrammar(ConnectionInterface $connection): SchemaGrammar
    {
        if (is_null($grammar = $connection->getSchemaGrammar())) {
            $connection->useDefaultSchemaGrammar();

            $grammar = $connection->getSchemaGrammar();
        }

        return $grammar;
    }

    public function resolveConnection(?string $connection = null): ConnectionInterface
    {
        return $this->resolver->connection($connection ?: $this->connection);
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