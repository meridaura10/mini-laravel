<?php

namespace Framework\Kernel\View;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\View\Concerns\ManagerComponentsTrait;
use Framework\Kernel\View\Concerns\ManagesLayouts;
use Framework\Kernel\View\Concerns\ManagesLoops;
use Framework\Kernel\View\Contracts\EngineInterface;
use Framework\Kernel\View\Contracts\EngineResolverInterface;
use Framework\Kernel\View\Contracts\FileViewFinderInterface;
use Framework\Kernel\View\Contracts\ViewFactoryInterface;
use Framework\Kernel\View\Contracts\ViewInterface;
use Framework\Kernel\View\Exceptions\InvalidArgumentException;

class ViewFactory implements ViewFactoryInterface
{
    use ManagerComponentsTrait;
    use ManagesLoops;
    use ManagesLayouts;
    protected array $extensions = [
        'blade.php' => 'blade',
        'php' => 'php',
    ];

    protected int $renderCount = 0;

    protected array $renderedOnce = [];

    protected array $shared = [];


    protected ?ApplicationInterface $app = null;

    public function __construct(
        protected EngineResolverInterface $engines,
        protected FileViewFinderInterface $finder,
    )
    {
        $this->share('__env', $this);
    }

    public function setContainer(ApplicationInterface $app): void
    {
        $this->app = $app;
    }

    public function make(string $view, array|Arrayable $data = [], array $mergeData = []): ViewInterface
    {
        $path = $this->finder->find(
            $view = $this->normalizeName($view),
        );

        $data = array_merge($mergeData, $this->parseData($data));

        return $this->viewInstance($view, $path, $data);
    }

    protected function viewInstance(string $view, string $path, array $data): ViewInterface
    {
        return new View($this, $this->getEngineFromPath($path), $view, $path, $data);
    }

    public function getEngineFromPath(string $path): EngineInterface
    {
        if (!$extension = $this->getExtension($path)) {
            throw new InvalidArgumentException("Unrecognized extension in file: {$path}.");
        }

        $engine = $this->extensions[$extension];

        return $this->engines->resolve($engine);
    }

    public function exists(string $view): bool
    {
        try {
            $this->finder->find($view);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    public function first(array $views,array $data = [],array $mergeData = []): ViewInterface
    {
        $view = Arr::first($views, function ($view) {
            return $this->exists($view);
        });

        if (! $view) {
            throw new InvalidArgumentException('None of the views in the given array exist.');
        }

        return $this->make($view, $data, $mergeData);
    }

    protected function getExtension($path): ?string
    {
        $extensions = array_keys($this->extensions);

        foreach ($extensions as $extension) {
            if (str_ends_with($path, '.' . $extension)) {
                return $extension;
            }
        }

        return null;
    }

    protected function parseData(Arrayable|array $data): array
    {
        return $data instanceof Arrayable ? $data->toArray() : $data;
    }

    protected function normalizeName(string $name): string
    {
        return ViewName::normalize($name);
    }

    public function share(array|string $key, mixed $value = null): mixed
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            $this->shared[$key] = $value;
        }

        return $value;
    }

    public function incrementRender(): void
    {
        $this->renderCount++;
    }

    public function decrementRender(): void
    {
        $this->renderCount--;
    }

    public function flushState(): void
    {
        $this->renderCount = 0;
        $this->renderedOnce = [];
    }

    public function flushStateIfDoneRendering(): void
    {
        if ($this->doneRendering()) {
            $this->flushState();
        }
    }

    public function doneRendering(): bool
    {
        return $this->renderCount == 0;
    }

    public function callComposer(ViewInterface $view): void
    {
        // event dd
    }

    public function getShared(): array
    {
        return $this->shared;
    }

    public function getFinder(): FileViewFinderInterface
    {
        return $this->finder;
    }
}
