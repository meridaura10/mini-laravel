<?php

namespace Framework\Kernel\Database\Traits;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Database\Eloquent\Casts\Attribute;
use Framework\Kernel\Database\Exceptions\MissingAttributeException;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;

trait HasAttributesTrait
{
    protected array $attributes = [];

    protected array $original = [];

    protected array $hidden = [];

    protected array $visible = [];

    protected array $casts = [];

    protected ?string $dateFormat = null;

    protected static array $attributeMutatorCache = [];
    protected static array $getAttributeMutatorCache = [];

    protected array $attributeCastCache = [];

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

//    protected function mergeAttributesFromCachedCasts()
//    {
//        $this->mergeAttributesFromClassCasts();
//        $this->mergeAttributesFromAttributeCasts();
//    }


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

        if (array_key_exists($key, $this->attributes) ||
            array_key_exists($key, $this->casts) ||
            $this->hasGetMutator($key) ||
            $this->hasAttributeMutator($key)) {
            return $this->getAttributeValue($key);
        }


        return $this->isRelation($key) || $this->relationLoaded($key)
            ? $this->getRelationValue($key)
            : $this->throwMissingAttributeExceptionIfApplicable($key);
    }

    public function getAttributeValue(string $key): mixed
    {
        return $this->transformModelValue($key, $this->getAttributeFromArray($key));
    }

    protected function getAttributeFromArray(string $key): mixed
    {
        return $this->getAttributes()[$key] ?? null;
    }

    protected function transformModelValue(string $key, mixed $value): mixed
    {
        if ($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, $value);
        } elseif ($this->hasAttributeGetMutator($key)) {
            return $this->mutateAttributeMarkedAttribute($key, $value);
        }

        return $value;
    }

    protected function mutateAttributeMarkedAttribute(string $key,mixed $value): mixed
    {
        if (array_key_exists($key, $this->attributeCastCache)) {
            return $this->attributeCastCache[$key];
        }

        $attribute = $this->{Str::camel($key)}();

        $value = call_user_func($attribute->get ?: function ($value) {
            return $value;
        }, $value, $this->attributes);

        if ($attribute->withCaching || (is_object($value) && $attribute->withObjectCaching)) {
            $this->attributeCastCache[$key] = $value;
        } else {
            unset($this->attributeCastCache[$key]);
        }

        return $value;
    }

    public function hasAttributeGetMutator(string $key): bool
    {
        if (isset(static::$getAttributeMutatorCache[get_class($this)][$key])) {
            return static::$getAttributeMutatorCache[get_class($this)][$key];
        }

        if (! $this->hasAttributeMutator($key)) {
            return static::$getAttributeMutatorCache[get_class($this)][$key] = false;
        }

        return static::$getAttributeMutatorCache[get_class($this)][$key] = is_callable($this->{Str::camel($key)}()->get);
    }

    protected function mutateAttribute(string $key,mixed $value): mixed
    {
        return $this->{'get'.Str::studly($key).'Attribute'}($value);
    }

    public function hasAttributeMutator(string $key): bool
    {
        if (isset(static::$attributeMutatorCache[get_class($this)][$key])) {
            return static::$attributeMutatorCache[get_class($this)][$key];
        }

        if (! method_exists($this, $method = Str::camel($key))) {
            return static::$attributeMutatorCache[get_class($this)][$key] = false;
        }

        $returnType = (new ReflectionMethod($this, $method))->getReturnType();

        return static::$attributeMutatorCache[get_class($this)][$key] =
            $returnType instanceof ReflectionNamedType &&
            $returnType->getName() === Attribute::class;
    }

    public function hasGetMutator(string $key): bool
    {
        return method_exists($this, 'get'.Str::studly($key).'Attribute');
    }

    protected function throwMissingAttributeExceptionIfApplicable(?string $key): null
    {
        if ($this->exists &&
            ! $this->wasRecentlyCreated &&
            static::preventsAccessingMissingAttributes()) {
            if (isset(static::$missingAttributeViolationCallback)) {
                return call_user_func(static::$missingAttributeViolationCallback, $this, $key);
            }

            throw new MissingAttributeException($this, $key);
        }

        return null;
    }

    public function getRelationValue(string $key): mixed
    {
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }


        if (! $this->isRelation($key)) {
            return null;
        }

        if ($this->preventsLazyLoading) {
            $this->handleLazyLoadingViolation($key);
        }

        return $this->getRelationshipFromMethod($key);
    }

    public function isRelation(string $key): bool
    {
        if($this->hasAttributeMutator($key)){
            return false;
        }

        return method_exists($this,$key) || $this->relationResolver(static::class, $key);
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
