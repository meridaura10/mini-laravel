<?php

namespace Framework\Kernel\Database\Traits;

trait GuardsAttributesTrait
{
    protected array $fillable = [];

    protected bool|array $guarded = ['*'];

    protected static bool $unguarded = false;

    protected static array $guardableColumns = [];

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function fillable(array $fillable): static
    {
        $this->fillable = $fillable;

        return $this;
    }

    public function getGuarded(): array
    {
        return is_array($this->guarded)
            ? $this->guarded
            : [];
    }

    public function guard(array $guarded): static
    {
        $this->guarded = $guarded;

        return $this;
    }

    public function totallyGuarded(): bool
    {
        return count($this->getFillable()) === 0 && $this->getGuarded() == ['*'];
    }

    protected function fillableFromArray(array $attributes): array
    {
        if (count($this->getFillable()) > 0 && ! static::$unguarded) {
            return array_intersect_key($attributes, array_flip($this->getFillable()));
        }

        return $attributes;
    }

    public function isGuarded(string $key): bool
    {
        if (empty($this->getGuarded())) {
            return false;
        }

        return $this->getGuarded() == ['*'] ||
            ! empty(preg_grep('/^'.preg_quote($key, '/').'$/i', $this->getGuarded())) ||
            ! $this->isGuardableColumn($key);
    }

    protected function isGuardableColumn(string $key): bool
    {
        return false;
        //        if (! isset(static::$guardableColumns[get_class($this)])) {
        //            $columns = $this->getConnection()
        //                ->getSchemaBuilder()
        //                ->getColumnListing($this->getTable());
        //
        //            if (empty($columns)) {
        //                return true;
        //            }
        //            static::$guardableColumns[get_class($this)] = $columns;
        //        }
        //
        //        return in_array($key, static::$guardableColumns[get_class($this)]);
    }

    public function isFillable(string $key)
    {
        if (static::$unguarded) {
            return true;
        }

        if (in_array($key, $this->getFillable())) {
            return true;
        }

        if ($this->isGuarded($key)) {
            return true;
        }

        return empty($this->getFillable()) &&
            ! str_contains($key, '.') &&
            ! str_starts_with($key, '_');
    }
}
