<?php

namespace Framework\Kernel\Support\Traits;

use Carbon\Carbon;

trait InteractsWithTimeTrait
{
    protected function availableAt(\DateInterval|\DateTimeInterface|int $delay = 0): int
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof \DateTimeInterface
            ? $delay->getTimestamp()
            : Carbon::now()->addRealSeconds($delay)->getTimestamp();
    }

    protected function parseDateInterval(\DateInterval|\DateTimeInterface|int $delay): \DateTimeInterface|int
    {
        if ($delay instanceof \DateInterval) {
            $delay = Carbon::now()->add($delay);
        }

        return $delay;
    }
}