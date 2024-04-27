<?php

namespace Framework\Kernel\Support;

use Carbon\CarbonInterval;
use DateInterval;
use RuntimeException;

class Sleep
{
    public CarbonInterval $duration;

    protected float|int|null $pending = null;

    protected bool $shouldSleep = true;

    public function __construct($duration)
    {
        $this->duration($duration);
    }


    public static function usleep(int $duration): static
    {
        return (new static($duration))->microseconds();
    }

    public function microseconds(): static
    {
        $this->duration->add('microseconds', $this->pullPending());

        return $this;
    }

    protected function shouldNotSleep(): static
    {
        $this->shouldSleep = false;

        return $this;
    }

    protected function pullPending(): float|int
    {
        if ($this->pending === null) {
            $this->shouldNotSleep();

            throw new RuntimeException('No duration specified.');
        }

        if ($this->pending < 0) {
            $this->pending = 0;
        }

        return tap($this->pending, function () {
            $this->pending = null;
        });
    }

    protected function duration($duration): static
    {
        if (! $duration instanceof DateInterval) {
            $this->duration = CarbonInterval::microsecond(0);

            $this->pending = $duration;
        } else {
            $duration = CarbonInterval::instance($duration);

            if ($duration->totalMicroseconds < 0) {
                $duration = CarbonInterval::seconds(0);
            }

            $this->duration = $duration;
            $this->pending = null;
        }

        return $this;
    }
}