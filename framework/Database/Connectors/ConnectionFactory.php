<?php

namespace Framework\Kernel\Database\Connectors;

use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Database\Connection;
use Framework\Kernel\Database\Connection\MySqlConnection;
use Framework\Kernel\Database\Connectors\Services\MySqlConnector;
use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Contracts\ConnectorInterface;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\View\Exceptions\InvalidArgumentException;
use PDOException;

class ConnectionFactory
{
    public function __construct(
        protected ApplicationInterface $app,
    ) {

    }

    public function make(array $config, string $name): ConnectionInterface
    {
        return $this->createSingleConnection($config);
    }

    protected function createSingleConnection(array $config): ConnectionInterface
    {
        $pdo = $this->createPdoResolver($config);

        return $this->createConnection(
            $config['driver'], $pdo, $config['database'], $config,
        );
    }

    protected function createConnection(string $driver, Closure $connection, string $database, array $config = []): ConnectionInterface
    {
        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $config);
        }

        return match ($driver) {
            'mysql' => new MySqlConnection($connection, $database, $config),
            default => throw new InvalidArgumentException("Unsupported driver [{$driver}]."),
        };
    }

    protected function createPdoResolver(array $config): Closure
    {
        return array_key_exists('host', $config)
            ? $this->createPdoResolverWithHosts($config)
            : $this->createPdoResolverWithoutHosts($config);
    }

    protected function createPdoResolverWithHosts(array $config): Closure
    {
        return function () use ($config) {
            foreach (Arr::shuffle($this->parseHosts($config)) as $host) {
                $config['host'] = $host;

                try {
                    return $this->createConnector($config)->connect($config);
                } catch (PDOException $e) {
                    continue;
                }
            }

            throw $e;
        };
    }

    protected function createPdoResolverWithoutHosts(array $config): Closure
    {
        return fn () => $this->createConnector($config)->connect($config);
    }

    protected function createConnector(array $config): ConnectorInterface
    {
        if (! isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

        if ($this->app->bound($key = "db.connector.{$config['driver']}")) {
            return $this->app->make($key);
        }

        return match ($config['driver']) {
            'mysql' => new MySqlConnector,
            default => throw new InvalidArgumentException("Unsupported driver [{$config['driver']}]."),
        };
    }

    protected function parseHosts(array $config): array
    {
        $hosts = Arr::wrap($config['host']);

        if (empty($hosts)) {
            throw new InvalidArgumentException('Database hosts array is empty.');
        }

        return $hosts;
    }
}
