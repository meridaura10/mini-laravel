<?php

namespace Framework\Kernel\Route;

class RouteGroup
{
    public static function merge(array $new, array $old): array
    {
        if (isset($new['controller'])) {
            unset($old['controller']);
        }

        $new = array_merge(static::formatAs($new, $old), [
            'prefix' => static::formatPrefix($new, $old),
        ]);

        // Arr

        return array_merge_recursive(array_diff_key(
            $old, array_flip(['prefix', 'name'])
        ), $new);
    }

    protected static function formatAs($new, $old)
    {
        if (isset($old['name'])) {
            $new['name'] = $old['name'].($new['name'] ?? '');
        }

        return $new;
    }

    protected static function formatPrefix($new, $old)
    {
        $old = $old['prefix'] ?? '';

        return isset($new['prefix']) ? trim($old, '/').'/'.trim($new['prefix'], '/') : $old;
    }
}
