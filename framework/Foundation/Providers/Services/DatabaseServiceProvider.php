<?php

namespace Framework\Kernel\Foundation\Providers\Services;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Database\Connectors\ConnectionFactory;
use Framework\Kernel\Database\DatabaseManager;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Foundation\Providers\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    protected static array $faker = [];

    public function boot(): void
    {
        Model::setConnectionResolver($this->app['db']);
    }

    public function register(): void
    {
        Model::clearBootedModels();

        $this->registerConnectionServices();
    }

    public function registerConnectionServices(): void
    {
        $this->app->singleton('db.factory', function (ApplicationInterface $app) {
            return new ConnectionFactory($app);
        });

        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });

        $this->app->bind('db.schema', function ($app) {
            return $app['db']->connection()->getSchemaBuilder();
        });
    }
}
