<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Connectors operations, generated from openapi.yaml. Do not edit by hand. */
class ConnectorsApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** List connector types. `GET /connector-types` @return array<string,mixed>|null */
    public function listConnectorTypes(): ?array
    {
        return $this->c->call('listConnectorTypes', [], [], null);
    }

    /** Dry-run a connector. `POST /locations/{locationId}/connectors/test` @return array<string,mixed>|null */
    public function testConnector(string $locationId, array $body): ?array
    {
        return $this->c->call('testConnector', ['locationId' => $locationId], [], $body);
    }

    /** List a location's connectors. `GET /locations/{locationId}/connectors` */
    public function listLocationConnectors(string $locationId, array $filters = []): Page
    {
        return $this->c->page('listLocationConnectors', ['locationId' => $locationId], $filters);
    }

    /** Every row of listLocationConnectors(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterLocationConnectors(string $locationId, array $filters = []): \Generator
    {
        return $this->c->iterate('listLocationConnectors', ['locationId' => $locationId], $filters);
    }

    /** Create a connector. `POST /locations/{locationId}/connectors` @return array<string,mixed>|null */
    public function createConnector(string $locationId, array $body): ?array
    {
        return $this->c->call('createConnector', ['locationId' => $locationId], [], $body);
    }

    /** Read a connector. `GET /locations/{locationId}/connectors/{connectorId}` @return array<string,mixed>|null */
    public function getConnector(string $locationId, string $connectorId): ?array
    {
        return $this->c->call('getConnector', ['locationId' => $locationId, 'connectorId' => $connectorId], [], null);
    }

    /** Update a connector. `PATCH /locations/{locationId}/connectors/{connectorId}` @return array<string,mixed>|null */
    public function updateConnector(string $locationId, string $connectorId, array $body): ?array
    {
        return $this->c->call('updateConnector', ['locationId' => $locationId, 'connectorId' => $connectorId], [], $body);
    }

    /** Stop a connector. `POST /locations/{locationId}/connectors/{connectorId}/deactivate` @return array<string,mixed>|null */
    public function deactivateConnector(string $locationId, string $connectorId): ?array
    {
        return $this->c->call('deactivateConnector', ['locationId' => $locationId, 'connectorId' => $connectorId], [], null);
    }

    /** Restart a connector. `POST /locations/{locationId}/connectors/{connectorId}/reactivate` @return array<string,mixed>|null */
    public function reactivateConnector(string $locationId, string $connectorId): ?array
    {
        return $this->c->call('reactivateConnector', ['locationId' => $locationId, 'connectorId' => $connectorId], [], null);
    }

    /** List a company's connectors. `GET /companies/{companyId}/connectors` */
    public function listCompanyConnectors(string $companyId, array $filters = []): Page
    {
        return $this->c->page('listCompanyConnectors', ['companyId' => $companyId], $filters);
    }

    /** Every row of listCompanyConnectors(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterCompanyConnectors(string $companyId, array $filters = []): \Generator
    {
        return $this->c->iterate('listCompanyConnectors', ['companyId' => $companyId], $filters);
    }
}
