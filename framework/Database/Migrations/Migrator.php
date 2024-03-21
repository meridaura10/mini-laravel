<?php

namespace Framework\Kernel\Database\Migrations;

use Framework\Kernel\Database\Contracts\ConnectionResolverInterface;
use Framework\Kernel\Database\Migrations\Contracts\MigrationRepositoryInterface;
use Framework\Kernel\Events\Contracts\DispatcherInterface;
use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;

class Migrator
{
    protected ?string $connection = null;

    public function __construct(
        protected MigrationRepositoryInterface $repository,
        protected ConnectionResolverInterface $resolver,
        protected FilesystemInterface $files,
        protected DispatcherInterface $events,
    ){

    }

    public function usingConnection(?string $name, callable $callback): mixed
    {
        $previousConnection = $this->resolver->getDefaultConnection();

        $this->setConnection($name);

        return tap($callback(), function () use ($previousConnection) {
            $this->setConnection($previousConnection);
        });
    }

    public function setConnection(?string $name): void
    {
        if($name){
            $this->resolver->setDefaultConnection($name);
        }

        $this->repository->setSource($name);

        $this->connection = $name;
    }

    public function repositoryExists(): bool
    {
        return $this->repository->repositoryExists();
    }
}