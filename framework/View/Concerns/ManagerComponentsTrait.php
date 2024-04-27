<?php

namespace Framework\Kernel\View\Concerns;

use Framework\Kernel\Contracts\Support\Htmlable;
use Framework\Kernel\View\Blade\ComponentSlot;
use Framework\Kernel\View\Contracts\ViewInterface;

trait ManagerComponentsTrait
{
    protected array $componentStack = [];

    protected array $componentData = [];

    protected array $currentComponentData = [];

    protected array $slots = [];

    public function startComponent(mixed $view, array $data = []): void
    {
        if (ob_start()) {
            $this->componentStack[] = $view;

            $this->componentData[$this->currentComponent()] = $data;

            $this->slots[$this->currentComponent()] = [];
        }
    }

    public function renderComponent()
    {
        $view = array_pop($this->componentStack);

        $this->currentComponentData = array_merge(
            $previousComponentData = $this->currentComponentData,
            $data = $this->componentData()
        );

        try {
            $view = value($view, $data);

            if ($view instanceof ViewInterface) {
                return $view->with($data)->render();
            } elseif ($view instanceof Htmlable) {
                return $view->toHtml();
            } else {
                return $this->make($view, $data)->render();
            }
        } finally {
            $this->currentComponentData = $previousComponentData;
        }
    }

    protected function componentData(): array
    {
        $defaultSlot = new ComponentSlot(trim(ob_get_clean()));

        $slots = array_merge([
            '__default' => $defaultSlot,
        ], $this->slots[count($this->componentStack)]);

        return array_merge(
            $this->componentData[count($this->componentStack)],
            ['slot' => $defaultSlot],
            $this->slots[count($this->componentStack)],
            ['__laravel_slots' => $slots]
        );
    }

    protected function currentComponent(): int
    {
        return count($this->componentStack) - 1;
    }
}