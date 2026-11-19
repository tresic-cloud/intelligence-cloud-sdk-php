<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api;

use IntelligenceCloud\Api\Generated\ConnectorsApiBase;
use IntelligenceCloud\Client;
use IntelligenceCloud\Exception\ConnectorError;
use IntelligenceCloud\Exception\ConnectorTimeoutError;

/**
 * Connector operations. Create, reactivate and deactivate answer 202 at once; with $wait (the
 * default) these methods check the connector every $checkInterval seconds (10), for up to the
 * client's connector timeout (600 seconds), until it settles. `error` throws ConnectorError
 * (`$stateReason`, e.g. `invalid_permissions`); no final status in time throws ConnectorTimeoutError.
 */
final class ConnectorsApi extends ConnectorsApiBase
{
    public float $checkInterval = 10.0;

    /**
     * Check the connector every $checkInterval seconds until its status is one of $final.
     *
     * @param list<string> $final
     * @return array<string,mixed> the connector
     */
    public function waitForStatus(string $locationId, string $connectorId, array $final, ?float $timeout = null, bool $raiseOnError = true): array
    {
        $timeout ??= $this->c->connectorTimeout();
        $waited = 0.0;
        while (true) {
            $conn = Client::unwrap($this->getConnector($locationId, $connectorId)) ?? [];
            $status = $conn['status'] ?? null;
            if ($status === 'error' && $raiseOnError) {
                throw ConnectorError::fromConnector($conn);
            }
            if (in_array($status, $final, true)) {
                return $conn;
            }
            if ($waited >= $timeout) {
                throw ConnectorTimeoutError::after($conn, $waited);
            }
            $this->c->pause($this->checkInterval);
            $waited += $this->checkInterval;
        }
    }

    /**
     * Create a connector and wait until it is `active`. Run testConnector first. Not idempotent:
     * on a lost response, list the location's connectors before retrying.
     *
     * @param array<string,mixed> $body name, type ("netsapiens"), config {api_url, domain, api_key}
     * @return array<string,mixed>|null
     */
    public function createConnector(string $locationId, array $body, bool $wait = true, ?float $timeout = null): ?array
    {
        $res = parent::createConnector($locationId, $body);
        if (!$wait) {
            return $res;
        }
        return ['data' => $this->waitForStatus($locationId, (string) Client::unwrap($res)['id'], ['active', 'error'], $timeout)];
    }

    /** @return array<string,mixed>|null */
    public function reactivateConnector(string $locationId, string $connectorId, bool $wait = true, ?float $timeout = null): ?array
    {
        $res = parent::reactivateConnector($locationId, $connectorId);
        return $wait ? ['data' => $this->waitForStatus($locationId, $connectorId, ['active', 'error'], $timeout)] : $res;
    }

    /** Stop a connector; waits for `inactive`. Monitored numbers are kept. @return array<string,mixed>|null */
    public function deactivateConnector(string $locationId, string $connectorId, bool $wait = true, ?float $timeout = null): ?array
    {
        $res = parent::deactivateConnector($locationId, $connectorId);
        return $wait ? ['data' => $this->waitForStatus($locationId, $connectorId, ['inactive'], $timeout, false)] : $res;
    }

    /** @return array<string,mixed>|null the location's connector with exactly this name */
    public function findConnector(string $locationId, string $name): ?array
    {
        foreach ($this->iterLocationConnectors($locationId, ['search' => $name]) as $c) {
            if (($c['name'] ?? null) === $name) {
                return $c;
            }
        }
        return null;
    }
}
