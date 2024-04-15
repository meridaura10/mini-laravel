<?php

namespace Framework\Kernel\Database\Factories;

use App\Models\Brand;
use App\Models\User;
use Closure;
use Faker\Generator;
use Framework\Kernel\Container\Container;
use Framework\Kernel\Database\Eloquent\EloquentCollection;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Support\Collection;
use Framework\Kernel\Support\Str;

abstract class Factory
{
    public static string $namespace = 'Database\\Factories\\';

    protected static ?Closure $factoryNameResolver = null;

    protected static ?Closure $modelNameResolver = null;

    protected Generator $faker;

    public function __construct(
        protected int         $count = 0,
        protected ?Collection $states = new Collection(),
        protected ?Collection $has = new Collection(),
        protected ?Collection $for = new Collection(),
        protected ?Collection $afterMaking = new Collection(),
        protected ?Collection $afterCreating = new Collection(),
        protected string|null $connection = null,
        protected ?Collection $recycle = new Collection()
    )
    {
       $this->faker = $this->withFaker();
    }

    protected function withFaker()
    {
        return Container::getInstance()->make(Generator::class);
    }

    public function create(array $attributes = [], ?Model $parent = null): EloquentCollection
    {
        $results = $this->make($attributes, $parent);

        $this->store($results);


        return $results;
    }

    public function for(Factory|Model $factory, ?string $relationship = null): static
    {
        return $this->newInstance(['for' => $this->for->concat([new BelongsToRelationship(
            $factory,
            $relationship ?? Str::camel(class_basename(
            $factory instanceof Factory ? $factory->modelName() : $factory
        ))
        )])]);
    }

    protected function store(Collection $results): void
    {
        $results->each(function (Model $model) {
            if (!isset($this->connection)) {
                $model->setConnection($model->newQueryWithoutScopes()->getConnection()->getName());
            }

            $model->save();

            $this->createChildren($model);
        });
    }

    protected function createChildren(Model $model): void
    {
        Model::unguarded(function () use ($model) {
            $this->has->each(function (RelationshipFactory $has) use ($model) {
                $has->recycle($this->recycle)->createFor($model);
            });
        });
    }


    public function recycle(Collection|Model $model): static
    {
        return $this->newInstance([
            'recycle' => $this->recycle
                ->flatten()
                ->merge(
                    Collection::wrap($model instanceof Model ? func_get_args() : $model)
                        ->flatten()
                )->groupBy(fn($model) => get_class($model)),
        ]);
    }

    public function make(array $attributes = [], ?Model $parent = null): EloquentCollection
    {
        if (!empty($attributes)) {
            return $this->state($attributes)->make();
        }

        return $this->newModel()->newCollection(array_map(function () use ($parent) {
            return $this->makeInstance($parent);
        }, range(1, $this->count)));
    }

    protected function makeInstance(?Model $parent = null)
    {
        return Model::unguarded(function () use ($parent) {
            return tap($this->newModel($this->getExpandedAttributes($parent)), function ($instance) {
                if (isset($this->connection)) {
                    $instance->setConnection($this->connection);
                }
            });
        });
    }

    protected function getExpandedAttributes(?Model $parent): array
    {
        return $this->expandAttributes($this->getRawAttributes($parent));
    }

    protected function getRawAttributes(?Model $parent): array
    {
        return $this->states->pipe(function ($states) {
            return $this->for->isEmpty() ? $states : new Collection(array_merge([function () {
                return $this->parentResolvers();
            }], $states->all()));
        })->reduce(function ($carry, $state) use ($parent) {
            if ($state instanceof Closure) {
                $state = $state->bindTo($this);
            }

            return array_merge($carry, $state($carry, $parent));
        }, $this->definition());
    }


    protected function parentResolvers(): array
    {
        $model = $this->newModel();


        return $this->for->map(function (BelongsToRelationship $for) use ($model) {
            return $for->recycle($this->recycle)->attributesFor($model);
        })->collapse()->all();
    }

    public function expandAttributes(array $definition = []): array
    {
        return collect($definition)
            ->map($evaluateRelations = function ($attribute) {
                if ($attribute instanceof self) {
                    $attribute = $this->getRandomRecycledModel($attribute->modelName())?->getKey()
                        ?? $attribute->recycle($this->recycle)->create()->first()->getKey();
                } elseif ($attribute instanceof Model) {
                    $attribute = $attribute->getKey();
                }

                return $attribute;
            })
            ->map(function ($attribute, $key) use (&$definition, $evaluateRelations) {
                if (is_callable($attribute) && !is_string($attribute) && !is_array($attribute)) {
                    $attribute = $attribute($definition);
                }

                $attribute = $evaluateRelations($attribute);

                $definition[$key] = $attribute;

                return $attribute;
            })
            ->all();
    }

    public function getRandomRecycledModel(string $modelClassName): ?Model
    {
        return $this->recycle->get($modelClassName)?->random();
    }

    public function newModel(array $attributes = []): Model
    {
        $model = $this->modelName();

        return new $model($attributes);
    }

    public function modelName(): string
    {
        $resolver = static::$modelNameResolver ?? function (self $factory) {
            $namespacedFactoryBasename = Str::replaceLast(
                'Factory',
                '',
                Str::replaceFirst(static::$namespace, '', get_class($factory))
            );

            $factoryBasename = Str::replaceLast('Factory', '', class_basename($factory));

            $appNamespace = static::appNamespace();

            return class_exists($appNamespace . 'Models\\' . $namespacedFactoryBasename)
                ? $appNamespace . 'Models\\' . $namespacedFactoryBasename
                : $appNamespace . $factoryBasename;
        };

        return $this->model ?? $resolver($this);
    }

    public static function factoryForModel(string $modelName): static
    {
        $factory = static::resolveFactoryName($modelName);

        return $factory::new();
    }

    public static function new($attributes = []): Factory
    {
        return (new static)->state($attributes)->configure();
    }

    public function has(self $factory, $relationship = null): static
    {
        return $this->newInstance([
            'has' => $this->has->concat([new RelationshipFactory(
                $factory,
                $relationship ?? $this->guessRelationship($factory->modelName())
            )]),
        ]);
    }

    protected function guessRelationship(string $related): string
    {
        $guess = Str::camel(Str::plural(class_basename($related)));

        return method_exists($this->modelName(), $guess) ? $guess : Str::singular($guess);
    }

    public function state(array|callable $state): static
    {
        return $this->newInstance([
            'states' => $this->states->concat([
                is_callable($state) ? $state : function () use ($state) {
                    return $state;
                },
            ]),
        ]);
    }

    public function count(?int $count): static
    {
        return $this->newInstance(['count' => $count]);
    }

    protected function newInstance(array $arguments = []): static
    {
        return new static(...array_values(array_merge([
            'count' => $this->count,
            'states' => $this->states,
            'has' => $this->has,
            'for' => $this->for,
            'afterMaking' => $this->afterMaking,
            'afterCreating' => $this->afterCreating,
            'connection' => $this->connection,
            'recycle' => $this->recycle,
        ], $arguments)));
    }

    public static function resolveFactoryName(string $modelName): string
    {
        $resolver = static::$factoryNameResolver ?? function (string $modelName) {
            $appNamespace = static::appNamespace();

            $modelName = Str::startsWith($modelName, $appNamespace . 'Models\\')
                ? Str::after($modelName, $appNamespace . 'Models\\')
                : Str::after($modelName, $appNamespace);

            return static::$namespace . $modelName . 'Factory';
        };

        return $resolver($modelName);
    }

    protected static function appNamespace(): string
    {
        return app()->getNamespace();
    }

    public function configure(): static
    {
        return $this;
    }
}