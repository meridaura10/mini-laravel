<?php

namespace Framework\Kernel\Database\Eloquent;

use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Contracts\ConnectionInterface;
use Framework\Kernel\Database\Contracts\ConnectionResolverInterface;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
use Framework\Kernel\Database\Exceptions\MassAssignmentException;
use Framework\Kernel\Database\Query\Support\Traits\ForwardsCallsTrait;
use Framework\Kernel\Database\Traits\GuardsAttributesTrait;
use Framework\Kernel\Database\Traits\HasAttributesTrait;
use Framework\Kernel\Database\Traits\HasTimestampsTrait;
use Framework\Kernel\Support\Str;

abstract class Model
{
    use ForwardsCallsTrait,
        GuardsAttributesTrait,
        HasAttributesTrait,
        HasTimestampsTrait;

    protected static ConnectionResolverInterface $resolver;

    protected ?string $connection = null;

    protected array $with = [];

    protected array $withCount = [];

    protected static array $booted = [];

    protected static array $globalScopes = [];

    protected ?string $table = null;

    protected bool $exists = false;

    protected bool $incrementing = true;

    protected string $primaryKey = 'id';

    protected string $keyType = 'int';

    public static function setConnectionResolver(ConnectionResolverInterface $resolver): void
    {
        static::$resolver = $resolver;
    }

    public static function clearBootedModels(): void
    {
        static::$booted = [];

        static::$globalScopes = [];
    }

    public static function query(): BuilderInterface
    {
        return (new static)->newQuery();
    }

    public function newCollection(array $models): EloquentCollection
    {
        return new EloquentCollection($models);
    }

    public function newInstance(array $attributes = [], bool $exists = false): static
    {
        $model = new static();

        $model->exists = $exists;

        $model->setConnection(
            $this->getConnectionName()
        );

        $model->setTable($this->getTable());

        $model->fill($attributes);

        return $model;
    }

    public function fill(array $attributes): static
    {
        $totallyGuarded = $this->totallyGuarded();

        $fillable = $this->fillableFromArray($attributes);

        foreach ($fillable as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);

                continue;
            }

            throw new MassAssignmentException(sprintf(
                'Add [%s] to fillable property to allow mass assignment on [%s].',
                $key, get_class($this)
            ));
        }

        return $this;
    }

    public function save(array $options = []): bool
    {
        $query = $this->newModelQuery();

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        if ($this->exists) {
            $saved = $this->isDirty() ?
                $this->performUpdate($query) : true;
        } else {
            $saved = $this->performInsert($query);

            if (! $this->getConnectionName() && $connection = $query->getConnection()) {
                $this->setConnection($connection->getName());
            }
        }

        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }

    protected function finishSave(array $options): void
    {
        $this->syncOriginal();
    }

    protected function performInsert(BuilderInterface $query): bool
    {
        $attributes = $this->getAttributes();

        if ($this->getIncrementing()) {
            $this->insertAndSetId($query, $attributes);
        } else {
            $query->insert($attributes);
        }

        $this->exists = true;

        return true;
    }

    protected function performUpdate(BuilderInterface $query): bool
    {
        $dirty = $this->getDirty();

        dd('performUpdate model');
    }

    protected function insertAndSetId(BuilderInterface $query, $attributes): void
    {
        $id = $query->insertGetId($attributes, $keyName = $this->getKeyName());

        $this->setAttribute($keyName, $id);
    }

    public function setConnection(?string $name): static
    {
        $this->connection = $name;

        return $this;
    }

    public function setTable(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function getTable(): string
    {
        return $this->table ?? Str::snake(Str::pluralStudly(class_basename($this)));
    }

    public function newFromBuilder(array $attributes = [], ?string $connection = null): static
    {
        $model = $this->newInstance([], true);

        $model->setRawAttributes($attributes, true);

        $model->setConnection($connection ?: $this->getConnectionName());

        return $model;
    }

    public function setRawAttributes(array $attributes, bool $sync = false): static
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    public static function unguarded(callable $callback): mixed
    {
        if (static::$unguarded) {
            return $callback();
        }

        static::unguard();

        try {
            return $callback();
        } finally {
            static::reguard();
        }
    }

    protected function newQuery(): BuilderInterface
    {
        return $this->registerGlobalScopes($this->newQueryWithoutScopes());
    }

    public function registerGlobalScopes(BuilderInterface $builder): BuilderInterface
    {
        //        foreach ($this->getGlobalScopes() as $identifier => $scope) {
        //            $builder->withGlobalScope($identifier, $scope);
        //        }
        //
        return $builder;
    }

    public function getGlobalScopes(): array
    {
        // not real

        return [];
    }

    public function getIncrementing(): bool
    {
        return $this->incrementing;
    }

    protected function newQueryWithoutScopes(): BuilderInterface
    {
        return $this->newModelQuery();
        //            ->with($this->with)
        //            ->withCount($this->withCount);
    }

    protected function newModelQuery(): BuilderInterface
    {
        return $this->newEloquentBuilder(
            $this->newBaseQueryBuilder()
        )->setModel($this);
    }

    protected function newBaseQueryBuilder(): QueryBuilderInterface
    {
        return $this->getConnection()->query();
    }

    protected function getConnection(): ConnectionInterface
    {
        return static::resolveConnection($this->getConnectionName());
    }

    protected function getConnectionName(): ?string
    {
        return $this->connection;
    }

    public function getForeignKey(): string
    {
        return Str::snake(class_basename($this)).'_'.$this->getKeyName();
    }

    public function getKeyType(): string
    {
        return $this->keyType;
    }

    public static function resolveConnection(?string $connection = null): ConnectionInterface
    {
        return static::$resolver->connection($connection);
    }

    protected function newEloquentBuilder(QueryBuilderInterface $query): BuilderInterface
    {
        return new Builder($query);
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        if (! $this->exists) {
            return false;
        }

        return $this->fill($attributes)->save($options);
    }

    protected function relationResolver(string $model, string $method): mixed
    {
        return null;
    }

    protected function through(string $method): mixed
    {
        return null;
    }

    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    public function __call($method, $parameters): mixed
    {
        if (in_array($method, ['increment', 'decrement', 'incrementQuietly', 'decrementQuietly'])) {
            return $this->$method(...$parameters);
        }

        if ($resolver = $this->relationResolver(static::class, $method)) {
            return $resolver($this);
        }

        if (strpos($method, 'through') &&
            method_exists($this, $relationMethod = lcfirst(substr($method, strlen('through'))))) {
            return $this->through($relationMethod);
        }

        return $this->forwardCallTo($this->newQuery(), $method, $parameters);
    }

    public static function __callStatic($method, $parameters): mixed
    {
        return (new static)->$method(...$parameters);
    }
}
