<?php

namespace Framework\Kernel\Database\Traits;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Support\Arr;

trait HasAttributesTrait
{
    protected array $attributes = [];

    protected array $original = [];

    protected array $hidden = [];

    protected array $visible = [];

    protected ?string $dateFormat = null;

    public function setAttribute($key, $value): static
    {
        if ($value instanceof Carbon) {
            $value = $this->fromDateTime($value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    public function fromDateTime(Carbon $value): string
    {
        return $value->format($this->getDateFormat());
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat ?: $this->getConnection()->getQueryGrammar()->getDateFormat();
    }

    public function isDirty($attributes = null): bool
    {
        return $this->hasChanges(
            $this->getDirty(), is_array($attributes) ? $attributes : func_get_args()
        );
    }

    public function getAttributes(): array
    {
        //        $this->mergeAttributesFromCachedCasts();

        return $this->attributes;
    }

    public function syncOriginal(): static
    {
        $this->original = $this->getAttributes();

        return $this;
    }

    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->getAttributes() as $key => $value) {
            if (! $this->originalIsEquivalent($key)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    public function originalIsEquivalent(string $key): bool
    {
        if (! array_key_exists($key, $this->original)) {
            return false;
        }

        $attribute = Arr::get($this->attributes, $key);
        $original = Arr::get($this->original, $key);

        if ($attribute === $original) {
            return true;
        } elseif (is_null($attribute)) {
            return false;
        }

        return false;
    }

    protected function hasChanges(array $changes, ?array $attributes = null): bool
    {

        if (empty($attributes)) {
            return count($changes) > 0;
        }

        foreach (Arr::wrap($attributes) as $attribute) {
            if (array_key_exists($attribute, $changes)) {
                return true;
            }
        }

        return false;
    }

    protected function getArrayableRelations(): array
    {
        return $this->getArrayableItems($this->relations);
    }

    protected function relationsToArray(): array
    {
        $attributes = [];

        foreach ($this->getArrayableRelations() as $key => $value) {
            if ($value instanceof Arrayable) {
                $relation = $value->toArray();
            } elseif (is_null($value)) {
                $relation = $value;
            }

            if (isset($relation) || is_null($value)) {
                $attributes[$key] = $relation;
            }

            unset($relation);
        }

        return $attributes;
    }

    public function attributesToArray(): array
    {
        $attributes = $this->addDateAttributesToArray(
            $attributes = $this->getArrayableAttributes()
        );

        return $attributes;
    }

    protected function addDateAttributesToArray(array $attributes): array
    {
        foreach ($this->getDates() as $key){
            if(! isset($this->attributes[$key])){
                continue;
            }

            $attributes[$key] = $this->serializeDate(
                $this->asDateTime($attributes[$key])
            );
        }

        return $attributes;
    }

    protected function asDateTime($value): Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        if ($this->isStandardDateFormat($value)) {
            return Carbon::instance(Carbon::createFromFormat('Y-m-d', $value)->startOfDay());
        }

        $format = $this->getDateFormat();


        try {
            $date = Carbon::createFromFormat($format, $value);
        } catch (\InvalidArgumentException) {
            $date = false;
        }

        return $date ?: Carbon::parse($value);
    }

    protected function isStandardDateFormat(mixed $value): string
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }


    public function getDates(): array
    {
        return $this->usesTimestamps() ? [
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ] : [];
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date instanceof \DateTimeImmutable ?
            CarbonImmutable::instance($date)->toJSON() :
            Carbon::instance($date)->toJSON();
    }

    protected function getArrayableAttributes(): array
    {
        return $this->getArrayableItems($this->getAttributes());
    }

    protected function getArrayableItems(array $values): array
    {
        if (count($this->getVisible()) > 0) {
            $values = array_intersect_key($values, array_flip($this->getVisible()));
        }

        if (count($this->getHidden()) > 0) {
            $values = array_diff_key($values, array_flip($this->getHidden()));
        }

        return $values;
    }

    public function getAttribute(?string $key): mixed
    {
        if (! $key) {
            return null;
        }

        if(array_key_exists($key, $this->attributes)){
            return $this->attributes[$key];
        }

        return null;
        // If the attribute exists in the attribute array or has a "get" mutator we will
        // get the attribute's value. Otherwise, we will proceed as if the developers
        // are asking for a relationship's value. This covers both types of values.
//        if (array_key_exists($key, $this->attributes) ||
//            array_key_exists($key, $this->casts) ||
//            $this->hasGetMutator($key) ||
//            $this->hasAttributeMutator($key) ||
//            $this->isClassCastable($key)) {
//            return $this->getAttributeValue($key);
//        }
//
//        // Here we will determine if the model base class itself contains this given key
//        // since we don't want to treat any of those methods as relationships because
//        // they are all intended as helper methods and none of these are relations.
//        if (method_exists(self::class, $key)) {
//            return $this->throwMissingAttributeExceptionIfApplicable($key);
//        }
//
//        return $this->isRelation($key) || $this->relationLoaded($key)
//            ? $this->getRelationValue($key)
//            : $this->throwMissingAttributeExceptionIfApplicable($key);
    }

    public function getVisible(): array
    {
        return $this->visible;
    }

    public function setVisible(array $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function getHidden(): array
    {
        return $this->hidden;
    }

    public function setHidden(array $hidden): static
    {
        $this->hidden = $hidden;

        return $this;
    }
}
