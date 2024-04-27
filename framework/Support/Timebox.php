<?php

namespace Framework\Kernel\Support;

use Throwable;

class Timebox
{
    public bool $earlyReturn = false;

    public function call(callable $callback, int $microseconds): mixed
    {
        $exception = null;

        $start = microtime(true);

        try {
            $result = $callback($this);
        } catch (Throwable $caught) {
            $exception = $caught;
        }

        $remainder = intval($microseconds - ((microtime(true) - $start) * 1000000));

        if (! $this->earlyReturn && $remainder > 0) {
            $this->usleep($remainder);
        }

        if ($exception) {
            throw $exception;
        }
        return $result;
    }

    public function returnEarly(): static
    {
        $this->earlyReturn = true;

        return $this;
    }

    protected function usleep(int $microseconds): void
    {
        Sleep::usleep($microseconds);
    }
}