<?php

declare(strict_types=1);

namespace IntelligenceCloud\Internal;

use IntelligenceCloud\Exception\FilterError;

/**
 * Checks and encodes query filters against the published spec before anything is sent.
 * The API silently ignores unknown parameters and returns unfiltered data, so every unknown or
 * malformed filter is refused here.
 *
 * @internal
 */
final class Query
{
    public const MAX_LOCATION_IDS = 50;
    public const MAX_NUMBERS = 25;
    private const UUID = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';

    /** Filters whose local offset matters (sent as given, not converted to UTC). */
    private const KEEP_OFFSET = ['today_start'];

    /**
     * @param array<string,array<string,mixed>> $defs  allowed parameters (SpecTables 'query')
     * @param array<string,mixed> $filters
     * @return array<string,string>
     */
    public static function build(string $operationId, array $defs, array $filters): array
    {
        $out = [];
        foreach ($filters as $name => $value) {
            if (!is_string($name)) {
                throw new FilterError(sprintf('%s: filters must be passed by name.', $operationId));
            }
            if (!isset($defs[$name])) {
                $allowed = array_keys($defs);
                throw new FilterError(sprintf(
                    '%s does not support the filter "%s". Supported: %s.',
                    $operationId,
                    $name,
                    $allowed ? implode(', ', $allowed) : 'none',
                ));
            }
            if ($value === null) {
                continue;
            }
            $out[$name] = self::encode($operationId, $name, $defs[$name], $value);
        }
        foreach ($defs as $name => $def) {
            if (!empty($def['required']) && !isset($out[$name])) {
                throw new FilterError(sprintf('%s needs the "%s" filter.', $operationId, $name));
            }
        }
        return $out;
    }

    /** @param array<string,mixed> $def */
    private static function encode(string $op, string $name, array $def, mixed $value): string
    {
        $type = $def['type'] ?? 'string';
        if ($type === 'array') {
            return self::encodeList($op, $name, $def, $value);
        }
        if ($type === 'boolean') {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            if (is_string($value) && in_array(strtolower($value), ['true', 'false'], true)) {
                return strtolower($value);
            }
            throw new FilterError(sprintf('%s: "%s" must be true or false.', $op, $name));
        }
        if ($type === 'integer') {
            if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
                $value = (int) $value;
            }
            if (!is_int($value)) {
                throw new FilterError(sprintf('%s: "%s" must be an integer.', $op, $name));
            }
            if ((isset($def['minimum']) && $value < $def['minimum']) || (isset($def['maximum']) && $value > $def['maximum'])) {
                throw new FilterError(sprintf(
                    '%s: "%s" must be between %s and %s.',
                    $op,
                    $name,
                    $def['minimum'] ?? '-∞',
                    $def['maximum'] ?? '∞',
                ));
            }
            return (string) $value;
        }
        // strings
        $format = $def['format'] ?? null;
        if ($format === 'date-time') {
            return Dates::timestamp($value, $name, in_array($name, self::KEEP_OFFSET, true));
        }
        if ($format === 'date') {
            return Dates::date($value, $name);
        }
        if (isset($def['enum']) && $def['enum'] === ['true']) {
            if ($value === true || $value === 'true') {
                return 'true';
            }
            throw new FilterError(sprintf('%s: "%s" only accepts true. Leave it out otherwise.', $op, $name));
        }
        if (is_bool($value) || is_array($value) || is_object($value)) {
            throw new FilterError(sprintf('%s: "%s" must be a string.', $op, $name));
        }
        $value = (string) $value;
        if ($format === 'uuid') {
            if (!preg_match(self::UUID, $value)) {
                throw new FilterError(sprintf('%s: "%s" must be a UUID.', $op, $name));
            }
            return strtolower($value);
        }
        if (isset($def['enum']) && !in_array($value, $def['enum'], true)) {
            throw new FilterError(sprintf('%s: "%s" must be one of: %s.', $op, $name, implode(', ', $def['enum'])));
        }
        if (isset($def['minLength']) && mb_strlen(trim($value)) < $def['minLength']) {
            throw new FilterError(sprintf(
                '%s: "%s" needs at least %d characters (shorter values are ignored by the API).',
                $op,
                $name,
                $def['minLength'],
            ));
        }
        return $value;
    }

    /** @param array<string,mixed> $def */
    private static function encodeList(string $op, string $name, array $def, mixed $value): string
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        if (!is_array($value)) {
            throw new FilterError(sprintf('%s: "%s" must be a list.', $op, $name));
        }
        $items = [];
        foreach ($value as $v) {
            if (!is_scalar($v)) {
                throw new FilterError(sprintf('%s: "%s" values must be strings.', $op, $name));
            }
            $v = trim((string) $v);
            if ($name === 'numbers') {
                $v = self::digits($v);
                if ($v === '') {
                    throw new FilterError(sprintf('%s: every "numbers" entry needs at least one digit.', $op));
                }
                $v = self::nanp10($v);
            }
            if ($v === '') {
                continue;
            }
            if (($def['itemFormat'] ?? null) === 'uuid') {
                if (!preg_match(self::UUID, $v)) {
                    throw new FilterError(sprintf('%s: "%s" values must be UUIDs.', $op, $name));
                }
                $v = strtolower($v);
            }
            $items[$v] = true;
        }
        $items = array_keys($items);
        $max = $def['maxItems'] ?? null;
        if ($name === 'location_id') {
            $max = self::MAX_LOCATION_IDS;
        } elseif ($name === 'numbers') {
            $max = min($max ?? self::MAX_NUMBERS, self::MAX_NUMBERS);
        }
        if ($max !== null && count($items) > $max) {
            throw new FilterError(sprintf(
                '%s: "%s" takes at most %d values per request (got %d). Split the request.',
                $op,
                $name,
                $max,
                count($items),
            ));
        }
        if ($items === []) {
            throw new FilterError(sprintf('%s: "%s" is empty.', $op, $name));
        }
        return implode(',', array_map('strval', $items));
    }

    /** Strip a phone number to digits: "+1 (555) 555-0142" becomes "15555550142". */
    public static function digits(string $number): string
    {
        return preg_replace('/\D+/', '', $number) ?? '';
    }

    /**
     * A North American number with its leading 1 ("15555550142") becomes its last 10 digits
     * ("5555550142"): numbers can be stored without the 1, and a search with it would miss them.
     */
    public static function nanp10(string $digits): string
    {
        return (strlen($digits) === 11 && $digits[0] === '1') ? substr($digits, 1) : $digits;
    }

    public static function isUuid(string $v): bool
    {
        return (bool) preg_match(self::UUID, $v);
    }
}
