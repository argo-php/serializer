<?php

declare(strict_types=1);

namespace Argo\Serializer;

/**
 * @api
 * @psalm-suppress PossiblyNullArgument,PossiblyNullArrayOffset
 */
final readonly class DataHelper
{
    /**
     * Get an item from an array or object using "dot" notation.
     */
    public static function get(mixed $target, array|int|string|null $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', (string) $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if (!is_iterable($target)) {
                    return self::value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = self::get($item, $key);
                }

                return in_array('*', $key) ? self::collapse($result) : $result;
            }

            if (self::accessible($target) && self::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return self::value($default);
            }
        }

        return $target;
    }

    /**
     * Set an item on an array or object using dot notation.
     */
    public static function set(mixed &$target, array|string $key, mixed $value, bool $overwrite = true): mixed
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
            if (!self::accessible($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    self::set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (self::accessible($target)) {
            if ($segments) {
                if (!self::exists($target, $segment)) {
                    $target[$segment] = [];
                }

                self::set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || ! self::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                self::set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || ! isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];

            if ($segments) {
                self::set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }

    public static function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof \Closure ? $value(...$args) : $value;
    }

    public static function exists(array|\ArrayAccess $array, int|string $key): bool
    {
        if ($array instanceof \ArrayAccess) {
            return $array->offsetExists($key);
        }
        return array_key_exists($key, $array);
    }

    /**
     * Collapse an array of arrays into a single array.
     */
    public static function collapse(array $array): array
    {
        $results = [];
        foreach ($array as $values) {
            if (!is_array($values)) {
                continue;
            }

            $results[] = $values;
        }
        return array_merge([], ...$results);
    }

    /**
     * Determine whether the given value is array accessible.
     */
    public static function accessible(mixed $value): bool
    {
        return is_array($value) || $value instanceof \ArrayAccess;
    }

    /**
     * Get the first element of an array. Useful for method chaining.
     */
    public static function head(array $array): mixed
    {
        return reset($array);
    }
}
