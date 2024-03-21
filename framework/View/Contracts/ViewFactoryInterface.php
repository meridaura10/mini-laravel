<?php

namespace Framework\Kernel\View\Contracts;

use Framework\Kernel\Contracts\Support\Arrayable;

interface ViewFactoryInterface
{
    public function make(string $view, Arrayable|array $data = [], array $mergeData = []): ViewInterface;

    public function share(array|string $key, mixed $value = null): mixed;

    public function incrementRender(): void;

    public function decrementRender(): void;

    public function flushState(): void;

    public function flushStateIfDoneRendering(): void;

    public function callComposer(ViewInterface $view): void;

    public function getShared(): array;
}
