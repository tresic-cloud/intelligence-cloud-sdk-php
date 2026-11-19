<?php

declare(strict_types=1);

namespace IntelligenceCloud\Lifecycle;

/** The Seat customer's location. `$vertical` is a vertical id, or its key or name from listVerticals(). */
final class LocationSpec
{
    public function __construct(
        public readonly string $name,
        public readonly string $street,
        public readonly string $city,
        public readonly string $state,
        public readonly string $country,
        public readonly string $zipcode,
        public readonly string $vertical,
    ) {
    }
}
