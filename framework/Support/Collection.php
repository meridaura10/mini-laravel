<?php

namespace Framework\Kernel\Support;

use ArrayAccess;
use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Contracts\Support\Jsonable;
use Framework\Kernel\Support\Traits\EnumeratesValuesTrait;
use Framework\Kernel\View\Exceptions\InvalidArgumentException;
use JsonSerializable;
use stdClass;
use Traversable;
use UnitEnum;
use WeakMap;

class Collection implements ArrayAccess, Enumerable
{
    use EnumeratesValuesTrait;

    protected array $items = [];

    public function __construct(mixed $items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    public function last(?callable $callback = null,mixed $default = null): mixed
    {
        return Arr::last($this->items, $callback, $default);
    }

    protected function getArrayableItems(mixed $items): array
    {
        if (is_array($items)) {
            return $items;
        }

        return match (true) {
            $items instanceof WeakMap => throw new InvalidArgumentException('Collections can not be created using instances of WeakMap.'),
            $items instanceof Enumerable => $items->all(),
            $items instanceof Arrayable => $items->toArray(),
            $items instanceof Traversable => iterator_to_array($items),
            $items instanceof Jsonable => json_decode($items->toJson(), true),
            $items instanceof JsonSerializable => (array) $items->jsonSerialize(),
            $items instanceof UnitEnum => [$items],
            default => (array) $items,
        };
    }

    public function pluck(array|string|int $value,?string $key = null): static
    {
        return new static(Arr::pluck($this->items, $value, $key));
    }

    public static function wrap(mixed $value): static
    {
        return $value instanceof Enumerable
            ? new static($value)
            : new static(Arr::wrap($value));
    }

    public function merge(iterable $items): static
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    public function each(callable $callback): static
    {
        foreach ($this as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    public function flatten(int|float $depth = INF): static
    {
        return new static(Arr::flatten($this->items, $depth));
    }

    public function groupBy($groupBy, $preserveKeys = false)
    {
        if (! $this->useAsCallable($groupBy) && is_array($groupBy)) {
            $nextGroups = $groupBy;

            $groupBy = array_shift($nextGroups);
        }

        $groupBy = $this->valueRetriever($groupBy);

        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);

            if (! is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }

            foreach ($groupKeys as $groupKey) {
                $groupKey = match (true) {
                    is_bool($groupKey) => (int) $groupKey,
                    $groupKey instanceof \BackedEnum => $groupKey->value,
                    $groupKey instanceof \Stringable => (string) $groupKey,
                    default => $groupKey,
                };

                if (! array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static;
                }

                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }

        $result = new static($results);

        if (! empty($nextGroups)) {
            return $result->map->groupBy($nextGroups, $preserveKeys);
        }

        return $result;
    }


    public function all(): array
    {
        return $this->items;
    }

    public function transform(callable $callback): static
    {
        $this->items = $this->map($callback)->all();

        return $this;
    }

    public function concat(iterable $source): static
    {
        $result = new static($this);
        foreach ($source as $item) {
            $result->push($item);
        }

        return $result;
    }



    public function push(...$values): static
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }

        return $this;
    }

    public function map(callable $callback): static
    {
        return new static(Arr::map($this->items, $callback));
    }

    public function first(?callable $callback = null, $default = null)
    {
        return Arr::first($this->items, $callback, $default);
    }

    public function get(string|int $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        return value($default);
    }

    public function only(string|int|null|array $keys): static
    {
        if (is_null($keys)) {
            return new static($this->items);
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        return new static(Arr::only($this->items, $keys));
    }

    public function filter(callable $callback = null): static
    {
        if ($callback) {
            return new static(Arr::where($this->items, $callback));
        }

        return new static(array_filter($this->items));
    }

    public function mapWithKeys(callable $callback): static
    {
        return new static(Arr::mapWithKeys($this->items, $callback));
    }

    public function contains(mixed $key,mixed $operator = null,mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            if ($this->useAsCallable($key)) {
                $placeholder = new stdClass;

                return $this->first($key, $placeholder) !== $placeholder;
            }

            return in_array($key, $this->items);
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    protected function operatorForWhere(callable|string $key, string|null $operator, mixed $value): \Closure
    {
        if ($this->useAsCallable($key)) {
            return $key;
        }

        if (func_num_args() === 1) {
            $value = true;

            $operator = '=';
        }

        if (func_num_args() === 2) {
            $value = $operator;

            $operator = '=';
        }

        return function ($item) use ($key,$operator,$value) {
            $retrieved = data_get($item, $key);

            $strings = array_filter([$retrieved, $value], function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }

            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
                case '<=>': return $retrieved <=> $value;
            }
        };
    }

    public function keyBy(array|string|callable $keyBy): static
    {
        $keyBy = $this->valueRetriever($keyBy);

        $results = [];

        foreach ($this->items as $key => $item) {
            $resolvedKey = $keyBy($item, $key);

            if (is_object($resolvedKey)) {
                $resolvedKey = (string) $resolvedKey;
            }

            $results[$resolvedKey] = $item;
        }

        return new static($results);
    }

    public function collapse(): static
    {
        return new static(Arr::collapse($this->items));
    }

    public function values(): static
    {
        return new static(array_values($this->items));
    }

    public function sortBy(array|string|callable $callback,int $options = SORT_REGULAR,bool $descending = false): static
    {
        if (is_array($callback) && ! is_callable($callback)) {
            return $this->sortByMany($callback);
        }

        $results = [];

        $callback = $this->valueRetriever($callback);

        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        $descending ? arsort($results, $options)
            : asort($results, $options);

        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        return new static($results);
    }

    public function reject(callable|bool $callback = true): static
    {
        $useAsCallable = $this->useAsCallable($callback);

        return $this->filter(function ($value, $key) use ($callback, $useAsCallable) {
            return $useAsCallable
                ? ! $callback($value, $key)
                : $value != $callback;
        });
    }

    protected function sortByMany(array $comparisons = []): static
    {
        $items = $this->items;

        uasort($items, function ($a, $b) use ($comparisons) {
            foreach ($comparisons as $comparison) {
                $comparison = Arr::wrap($comparison);

                $prop = $comparison[0];

                $ascending = Arr::get($comparison, 1, true) === true ||
                    Arr::get($comparison, 1, true) === 'asc';

                if (! is_string($prop) && is_callable($prop)) {
                    $result = $prop($a, $b);
                } else {
                    $values = [data_get($a, $prop), data_get($b, $prop)];

                    if (! $ascending) {
                        $values = array_reverse($values);
                    }

                    $result = $values[0] <=> $values[1];
                }

                if ($result === 0) {
                    continue;
                }

                return $result;
            }
        });

        return new static($items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }


    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function toArray(): array
    {
        return $this->map(fn ($value) => $value instanceof Arrayable ? $value->toArray() : $value)->all();
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function jsonSerialize(): array
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof Jsonable) {
                return json_decode($value->toJson(), true);
            } elseif ($value instanceof Arrayable) {
                return $value->toArray();
            }

            return $value;
        }, $this->all());
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
