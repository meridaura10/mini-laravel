<?php

namespace Framework\Kernel\Database\Traits;

use Carbon\Carbon;

trait HasTimestampsTrait
{
    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected bool $timestamps = true;

    public function freshTimestamp(): Carbon
    {
        return Carbon::now();
    }

    protected function updateTimestamps(): static
    {
        $time = $this->freshTimestamp();

        $updatedAtColumn = $this->getUpdatedAtColumn();

        if (! is_null($updatedAtColumn) && ! $this->isDirty($updatedAtColumn)) {
            $this->setUpdatedAt($time);
        }

        $createdAtColumn = $this->getCreatedAtColumn();

        if (! $this->exists && ! is_null($createdAtColumn) && ! $this->isDirty($createdAtColumn)) {
            $this->setCreatedAt($time);
        }

        return $this;
    }

    public function setUpdatedAt(Carbon $value): static
    {
        $this->{$this->getUpdatedAtColumn()} = $value;

        return $this;
    }

    public function setCreatedAt(Carbon $value): static
    {
        $this->{$this->getCreatedAtColumn()} = $value;

        return $this;
    }

    public function getUpdatedAtColumn(): ?string
    {
        return static::UPDATED_AT;
    }

    public function getCreatedAtColumn(): ?string
    {
        return static::CREATED_AT;
    }

    public function usesTimestamps(): bool
    {
        return $this->timestamps;
    }
}
