<?php

namespace Framework\Kernel\View;

use Framework\Kernel\Contracts\Support\Renderable;
use Framework\Kernel\View\Contracts\EngineInterface;
use Framework\Kernel\View\Contracts\ViewFactoryInterface;
use Framework\Kernel\View\Contracts\ViewInterface;
use Throwable;

class View implements ViewInterface
{
    public function __construct(
        protected ViewFactoryInterface $factory,
        protected EngineInterface $engine,
        protected string $view,
        protected string $path,
        protected array $data,
    ) {

    }

    public function render(?callable $callback = null): string
    {
        try {
            $contents = $this->renderContents();

            $response = isset($callback) ? $callback($this, $contents) : null;

            $this->factory->flushStateIfDoneRendering();

            return $response ? $response : $contents;
        } catch (Throwable $e) {
            $this->factory->flushState();
            throw $e;
        }

    }

    protected function renderContents(): string
    {
        $this->factory->incrementRender();

        $this->factory->callComposer($this);

        $contents = $this->getContents();

        $this->factory->decrementRender();

        return $contents;
    }

    protected function getContents(): string
    {
        return $this->engine->get($this->path, $this->gatherData());
    }

    protected function gatherData(): array
    {
        $data = array_merge($this->factory->getShared(), $this->data);

        foreach ($data as $key => $value) {
            if ($value instanceof Renderable) {
                $data[$key] = $value->render();
            }
        }

        return $data;
    }
}
