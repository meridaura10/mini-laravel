<?php

namespace Framework\Kernel\Database\Traits;

use Carbon\Carbon;
use Framework\Kernel\Support\Arr;

trait HasAttributesTrait
{
    protected array $attributes = [];

    protected array $original = [];

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
}
