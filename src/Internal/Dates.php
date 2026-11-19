<?php

declare(strict_types=1);

namespace IntelligenceCloud\Internal;

use IntelligenceCloud\Exception\FilterError;

/** @internal */
final class Dates
{
    private const RFC3339 = '/^\d{4}-\d{2}-\d{2}[Tt]\d{2}:\d{2}:\d{2}(\.\d+)?([Zz]|[+-]\d{2}:\d{2})$/';

    /**
     * A full RFC 3339 timestamp. DateTimeInterface values and strings are converted to UTC
     * (`2026-11-01T06:00:00Z`) unless $keepOffset is true. Date-only strings are refused.
     */
    public static function timestamp(mixed $value, string $name, bool $keepOffset = false): string
    {
        if ($value instanceof \DateTimeInterface) {
            if ($keepOffset) {
                return $value->format('Y-m-d\TH:i:sP');
            }
            return \DateTimeImmutable::createFromInterface($value)
                ->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
        }
        if (!is_string($value) || !preg_match(self::RFC3339, $value)) {
            throw new FilterError(sprintf(
                '%s needs a full RFC 3339 timestamp, e.g. 2026-11-01T00:00:00Z (a date alone is refused by the API).',
                $name,
            ));
        }
        if ($keepOffset) {
            return $value;
        }
        try {
            $dt = new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new FilterError(sprintf('%s is not a valid timestamp.', $name));
        }
        return $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    /** A calendar date `YYYY-MM-DD`. */
    public static function date(mixed $value, string $name): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new FilterError(sprintf('%s needs a date in the form YYYY-MM-DD.', $name));
        }
        return $value;
    }
}
