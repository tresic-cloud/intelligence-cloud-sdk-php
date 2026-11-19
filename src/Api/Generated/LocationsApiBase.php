<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Locations operations, generated from openapi.yaml. Do not edit by hand. */
class LocationsApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** List industries. `GET /verticals` @return array<string,mixed>|null */
    public function listVerticals(): ?array
    {
        return $this->c->call('listVerticals', [], [], null);
    }

    /** List a company's locations. `GET /companies/{companyId}/locations` */
    public function listCompanyLocations(string $companyId, array $filters = []): Page
    {
        return $this->c->page('listCompanyLocations', ['companyId' => $companyId], $filters);
    }

    /** Every row of listCompanyLocations(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterCompanyLocations(string $companyId, array $filters = []): \Generator
    {
        return $this->c->iterate('listCompanyLocations', ['companyId' => $companyId], $filters);
    }

    /** Create a location. `POST /companies/{companyId}/locations` @return array<string,mixed>|null */
    public function createLocation(string $companyId, array $body): ?array
    {
        return $this->c->call('createLocation', ['companyId' => $companyId], [], $body);
    }

    /** Read a location. `GET /companies/{companyId}/locations/{locationId}` @return array<string,mixed>|null */
    public function getLocation(string $companyId, string $locationId): ?array
    {
        return $this->c->call('getLocation', ['companyId' => $companyId, 'locationId' => $locationId], [], null);
    }

    /** Update a location. `PATCH /companies/{companyId}/locations/{locationId}` @return array<string,mixed>|null */
    public function updateLocation(string $companyId, string $locationId, array $body): ?array
    {
        return $this->c->call('updateLocation', ['companyId' => $companyId, 'locationId' => $locationId], [], $body);
    }

    /** Deactivate a location. `POST /companies/{companyId}/locations/{locationId}/deactivate` @return array<string,mixed>|null */
    public function deactivateLocation(string $companyId, string $locationId): ?array
    {
        return $this->c->call('deactivateLocation', ['companyId' => $companyId, 'locationId' => $locationId], [], null);
    }

    /** Reactivate a location. `POST /companies/{companyId}/locations/{locationId}/reactivate` @return array<string,mixed>|null */
    public function reactivateLocation(string $companyId, string $locationId): ?array
    {
        return $this->c->call('reactivateLocation', ['companyId' => $companyId, 'locationId' => $locationId], [], null);
    }
}
