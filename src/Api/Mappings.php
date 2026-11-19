<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api;

use IntelligenceCloud\Exception\FilterError;

/** @internal merge helpers for number mappings */
final class Mappings
{
    /**
     * Values of $existing + $add in order, without duplicates and without $remove.
     *
     * @param iterable<string> $existing
     * @param iterable<string> $add
     * @param iterable<string> $remove
     * @return list<string>
     */
    public static function merge(iterable $existing, iterable $add = [], iterable $remove = []): array
    {
        $drop = [];
        foreach ($remove as $r) {
            $drop[(string) $r] = true;
        }
        $out = [];
        foreach ([$existing, $add] as $list) {
            foreach ($list as $v) {
                $v = (string) $v;
                if (!isset($drop[$v]) && !in_array($v, $out, true)) {
                    $out[] = $v;
                }
            }
        }
        return $out;
    }

    /**
     * Number mappings in request form, with numbers added to / removed from one connector.
     * Mappings left with no numbers are dropped (the API refuses empty lists).
     *
     * @param array<int,array<string,mixed>> $mappings
     * @param iterable<string> $add
     * @param iterable<string> $remove
     * @return list<array{connector_id:string, monitored_numbers:list<string>}>
     */
    public static function edit(array $mappings, string $connectorId, iterable $add = [], iterable $remove = []): array
    {
        $add = self::clean($add);
        $out = [];
        $found = false;
        foreach ($mappings as $m) {
            if (!is_array($m) || !isset($m['connector_id'])) {
                continue;
            }
            $nums = array_values(array_map('strval', $m['monitored_numbers'] ?? []));
            if ($m['connector_id'] === $connectorId) {
                $found = true;
                $nums = self::merge($nums, $add, $remove);
            }
            if ($nums !== []) {
                $out[] = ['connector_id' => (string) $m['connector_id'], 'monitored_numbers' => $nums];
            }
        }
        if (!$found) {
            $nums = self::merge([], $add, $remove);
            if ($nums !== []) {
                $out[] = ['connector_id' => $connectorId, 'monitored_numbers' => $nums];
            }
        }
        return $out;
    }

    /**
     * The numbers on one connector (or all connectors), in order, without duplicates.
     *
     * @param array<int,array<string,mixed>> $mappings
     * @return list<string>
     */
    public static function numbersFor(array $mappings, ?string $connectorId = null): array
    {
        $out = [];
        foreach ($mappings as $m) {
            if (is_array($m) && ($connectorId === null || ($m['connector_id'] ?? null) === $connectorId)) {
                $out = self::merge($out, $m['monitored_numbers'] ?? []);
            }
        }
        return $out;
    }

    /**
     * @param iterable<string> $numbers
     * @return list<string>
     */
    private static function clean(iterable $numbers): array
    {
        $out = [];
        foreach ($numbers as $n) {
            $v = trim((string) $n);
            if ($v === '') {
                throw new FilterError('numbers: empty entry');
            }
            if (!in_array($v, $out, true)) {
                $out[] = $v;
            }
        }
        return $out;
    }
}
