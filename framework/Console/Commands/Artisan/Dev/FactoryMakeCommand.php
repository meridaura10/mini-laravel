<?php

namespace Framework\Kernel\Console\Commands\Artisan\Dev;

use Framework\Kernel\Console\GeneratorCommand;
use Framework\Kernel\Console\Input\InputOption;
use Framework\Kernel\Support\Str;

class FactoryMakeCommand extends GeneratorCommand
{
    protected ?string $name = 'make:factory';

    protected ?string $description = 'Create a new model factory';

    protected string $type = 'factory';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/factory.stub');
    }

    public function buildClass(string $name): string
    {
        $factory = class_basename(Str::ucfirst(str_replace('Factory', '', $name)));

        $namespaceModel = $this->option('model')
            ? $this->qualifyModel($this->option('model'))
            : $this->qualifyModel($this->guessModelName($name));

        $model = class_basename($namespaceModel);


        $namespace = $this->getNamespace(
            Str::replaceFirst($this->rootNamespace(), 'Database\\Factories\\', $this->qualifyClass($this->getNameInput()))
        );

        $replace = [
            '{{ factoryNamespace }}' => $namespace,
            'NamespacedDummyModel' => $namespaceModel,
            '{{ namespacedModel }}' => $namespaceModel,
            '{{namespacedModel}}' => $namespaceModel,
            'DummyModel' => $model,
            '{{ model }}' => $model,
            '{{model}}' => $model,
            '{{ factory }}' => $factory,
            '{{factory}}' => $factory,
        ];

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    protected function getPath(string $name): string
    {
        $name = (string) Str::of($name)->replaceFirst($this->rootNamespace(), '')->finish('Factory');

        return $this->app->databasePath().'/factories/'.str_replace('\\', '/', $name).'.php';
    }

    protected function guessModelName(string $name): string
    {

        if (str_ends_with($name, 'Factory')) {
            $name = substr($name, 0, -7);
        }

        $modelName = $this->qualifyModel(Str::after($name, $this->rootNamespace()));

        if (class_exists($modelName)) {
            return $modelName;
        }

        if (is_dir(app_path('Model/'))) {
            return $this->rootNamespace().'Model\Model';
        }

        return $this->rootNamespace().'Model';
    }

    protected function getOptions(): array
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The name of the model'],
        ];
    }
}