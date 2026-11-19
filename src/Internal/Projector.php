<?php

declare(strict_types=1);

namespace IntelligenceCloud\Internal;

/**
 * Drops every field that is not in the published schema. This is a fail-safe: fields the API
 * adds that are not part of the partner contract never reach your code.
 *
 * @internal
 */
final class Projector
{
    /** @param mixed $tree projection tree from SpecTables (1, ['o'=>…], ['a'=>…]) */
    public static function project(mixed $value, mixed $tree): mixed
    {
        if ($tree === 1 || $value === null || !is_array($tree)) {
            return $value;
        }
        if (isset($tree['a'])) {
            if (!is_array($value) || !array_is_list($value)) {
                return $value;
            }
            return array_map(static fn ($v) => self::project($v, $tree['a']), $value);
        }
        if (isset($tree['o'])) {
            if (!is_array($value) || ($value !== [] && array_is_list($value))) {
                return $value;
            }
            if (!empty($tree['x'])) {
                return $value;
            }
            $out = [];
            foreach ($tree['o'] as $key => $sub) {
                if (array_key_exists($key, $value)) {
                    $out[$key] = self::project($value[$key], $sub);
                }
            }
            return $out;
        }
        return $value;
    }
}
