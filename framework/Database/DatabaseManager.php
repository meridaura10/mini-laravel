<?php

namespace Framework\Kernel\Database;

use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Database\Connectors\ConnectionFactory;
use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Contracts\ConnectionResolverInterface;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Str;
use Framework\Kernel\View\Exceptions\InvalidArgumentException;

class DatabaseManager implements ConnectionResolverInterface
{
    protected array $connections = [];

    protected array $extensions = [];

    protected ?Closure $reconnector = null;

    public function __construct(
        protected ApplicationInterface $app,
        protected ConnectionFactory $factory,
    ) {
        $this->reconnector = function ($connection) {
            $this->reconnect($connection->getNameWithReadWriteType());
        };
    }

    public function reconnect(?string $name = null): ConnectionInterface
    {
        $this->disconnect($name = $name ?: $this->getDefaultConnection());

        if (! isset($this->connections[$name])) {
            return $this->connection($name);
        }

        return $this->refreshPdoConnections($name);
    }

    protected function refreshPdoConnections(string $name): ConnectionInterface
    {
        [$database, $type] = $this->parseConnectionName($name);

        $fresh = $this->configure(
            $this->makeConnection($database), $type
        );

        return $this->connections[$name]
            ->setPdo($fresh->getRawPdo())
            ->setReadPdo($fresh->getRawReadPdo());
    }

    public function disconnect(?string $name = null): void
    {
        if (isset($this->connections[$name = $name ?: $this->getDefaultConnection()])) {
            $this->connections[$name]->disconnect();
        }
    }

    public function connection(?string $name = null): ConnectionInterface
    {
        [$database, $type] = $this->parseConnectionName($name);

        $name = $name ?: $database;

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->configure(
                $this->makeConnection($database), $type
            );
        }

        return $this->connections[$name];
    }

    protected function makeConnection(string $name): ConnectionInterface
    {
        $config = $this->configuration($name);

        if (isset($this->extensions[$name])) {
            return call_user_func($this->extensions[$name], $config, $name);
        }

        if (isset($this->extensions[$driver = $config['driver']])) {
            return call_user_func($this->extensions[$driver], $config, $name);
        }

        return $this->factory->make($config, $name);
    }

    protected function configuration(string $name): array
    {
        $name = $name ?: $this->getDefaultConnection();

        $connections = $this->app['config']['database.connections'];

        if (is_null($config = Arr::get($connections, $name))) {
            throw new InvalidArgumentException("Database connection [{$name}] not configured.");
        }

        return $config;
    }

    protected function configure(ConnectionInterface $connection, ?string $type = null): ConnectionInterface
    {
        $connection = $this->setPdoForType($connection, $type)
            ->setReadWriteType($type)
            ->setReconnector($this->reconnector);

        return $connection;
    }

    protected function setPdoForType(ConnectionInterface $connection, ?string $type = null): ConnectionInterface
    {
        //read or write

        return $connection;
    }

    protected function parseConnectionName(?string $name = null): array
    {
        $name = $name ?: $this->getDefaultConnection();

        return Str::endsWith($name, ['::read', '::write'])
            ? explode('::', $name, 2) : [$name, null];
    }

    public function getDefaultConnection(): string
    {
        return $this->app['config']['database.default'];
    }

    public function setDefaultConnection(string $name): void
    {
        $this->app['config']['database.default'] = $name;
    }
}
