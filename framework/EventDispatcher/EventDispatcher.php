<?php

namespace Framework\Kernel\EventDispatcher;

use Framework\Kernel\EventDispatcher\Contracts\EventDispatcherInterface;
use Framework\Kernel\EventDispatcher\Contracts\StoppableEventInterface;

class EventDispatcher implements EventDispatcherInterface
{
    protected array $optimized = [];

    protected array $listeners = [];

    protected array $sorted = [];

    public function __construct()
    {
        if (static::class === __CLASS__) {
            $this->optimized = [];
        }
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        $eventName = $eventName ?? $event::class;

        if (isset($this->optimized)) {
            $listeners = $this->optimized[$eventName] ?? (empty($this->listeners[$eventName]) ? [] : $this->optimizeListeners($eventName));
        } else {
            $listeners = $this->getListeners($eventName);
        }

        if ($listeners) {
            $this->callListeners($listeners, $eventName, $event);
        }

        return $event;
    }

    public function getListeners(?string $eventName = null): array
    {
        if ($eventName) {
            if (empty($this->listeners[$eventName])) {
                return [];
            }

            if (! isset($this->sorted[$eventName])) {
                $this->sortListeners($eventName);
            }

            return $this->sorted[$eventName];
        }

        foreach ($this->listeners as $eventName => $eventListeners) {
            if (! isset($this->sorted[$eventName])) {
                $this->sortListeners($eventName);
            }
        }

        return array_filter($this->sorted);
    }

    private function sortListeners(string $eventName): void
    {
        krsort($this->listeners[$eventName]);
        $this->sorted[$eventName] = [];

        foreach ($this->listeners[$eventName] as &$listeners) {
            foreach ($listeners as &$listener) {
                if (\is_array($listener) && isset($listener[0]) && $listener[0] instanceof \Closure && \count($listener) <= 2) {
                    $listener[0] = $listener[0]();
                    $listener[1] ??= '__invoke';
                }
                $this->sorted[$eventName][] = $listener;
            }
        }
    }

    protected function callListeners(iterable $listeners, string $eventName, object $event): void
    {
        $stoppable = $event instanceof StoppableEventInterface;

        foreach ($listeners as $listener) {
            if ($stoppable && $event->isPropagationStopped()) {
                break;
            }

            $listener($event, $eventName, $this);
        }
    }

    private function optimizeListeners(string $eventName): array
    {
        krsort($this->listeners[$eventName]);
        $this->optimized[$eventName] = [];

        foreach ($this->listeners[$eventName] as &$listeners) {
            foreach ($listeners as &$listener) {
                $closure = &$this->optimized[$eventName][];
                if (\is_array($listener) && isset($listener[0]) && $listener[0] instanceof \Closure && \count($listener) <= 2) {
                    $closure = static function (...$args) use (&$listener, &$closure) {
                        if ($listener[0] instanceof \Closure) {
                            $listener[0] = $listener[0]();
                            $listener[1] ??= '__invoke';
                        }
                        ($closure = $listener(...))(...$args);
                    };
                } else {
                    $closure = $listener instanceof WrappedListener ? $listener : $listener(...);
                }
            }
        }

        return $this->optimized[$eventName];
    }
}
