<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api;

use IntelligenceCloud\Api\Generated\LocationsApiBase;
use IntelligenceCloud\Client;

/** Locations operations, plus read-merge-write product helpers and a name lookup. */
final class LocationsApi extends LocationsApiBase
{
    /**
     * Switch products on at the location. They must be on for the company first.
     *
     * @param iterable<string> $productKeys
     * @return array<string,mixed>|null
     */
    public function addProducts(string $companyId, string $locationId, iterable $productKeys): ?array
    {
        $cur = Client::unwrap($this->getLocation($companyId, $locationId))['enabled_products'] ?? [];
        return $this->updateLocation($companyId, $locationId, ['enabled_products' => Mappings::merge($cur, $productKeys)]);
    }

    /**
     * @param iterable<string> $productKeys
     * @return array<string,mixed>|null
     */
    public function removeProducts(string $companyId, string $locationId, iterable $productKeys): ?array
    {
        $cur = Client::unwrap($this->getLocation($companyId, $locationId))['enabled_products'] ?? [];
        return $this->updateLocation($companyId, $locationId, ['enabled_products' => Mappings::merge($cur, [], $productKeys)]);
    }

    /** @return array<string,mixed>|null the location with exactly this name */
    public function findLocation(string $companyId, string $locationName): ?array
    {
        foreach ($this->iterCompanyLocations($companyId, ['search' => $locationName]) as $l) {
            if (($l['location_name'] ?? null) === $locationName) {
                return $l;
            }
        }
        return null;
    }
}
