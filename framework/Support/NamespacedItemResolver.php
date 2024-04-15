<?php

namespace Framework\Kernel\Support;

class NamespacedItemResolver
{
    protected array $parsed = [];

    public function parseKey(string $key): array
    {
        if (isset($this->parsed[$key])) {
            return $this->parsed[$key];
        }

        if (! str_contains($key, '::')) {
            $segments = explode('.', $key);

            $parsed = $this->parseBasicSegments($segments);
        } else {
            $parsed = $this->parseNamespacedSegments($key);
        }

        return $this->parsed[$key] = $parsed;
    }

    protected function parseNamespacedSegments(string $key): array
    {
        [$namespace, $item] = explode('::', $key);

        $itemSegments = explode('.', $item);

        $groupAndItem = array_slice(
            $this->parseBasicSegments($itemSegments), 1
        );

        return array_merge([$namespace], $groupAndItem);
    }

    protected function parseBasicSegments(array $segments): array
    {
        $group = $segments[0];

        $item = count($segments) === 1
            ? null
            : implode('.', array_slice($segments, 1));

        return [null, $group, $item];
    }
}