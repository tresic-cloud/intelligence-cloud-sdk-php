<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api;

use IntelligenceCloud\Api\Generated\InsightsHubApiBase;
use IntelligenceCloud\Client;

/** Insights Hub operations, plus read-merge-write helpers for numbers and group access. */
final class InsightsHubApi extends InsightsHubApiBase
{
    /**
     * Add numbers to the location's Insights Hub (read, merge, PATCH `connector_mappings`).
     *
     * @param iterable<string> $numbers
     * @return array<string,mixed>|null
     */
    public function addNumbers(string $locationId, string $connectorId, iterable $numbers): ?array
    {
        return $this->updateInsightsHubSetup($locationId, ['connector_mappings' => Mappings::edit($this->setup($locationId)['connector_mappings'] ?? [], $connectorId, $numbers)]);
    }

    /**
     * @param iterable<string> $numbers
     * @return array<string,mixed>|null
     */
    public function removeNumbers(string $locationId, string $connectorId, iterable $numbers): ?array
    {
        return $this->updateInsightsHubSetup($locationId, ['connector_mappings' => Mappings::edit($this->setup($locationId)['connector_mappings'] ?? [], $connectorId, [], $numbers)]);
    }

    /**
     * Replace the numbers for one connector; other connectors are kept.
     *
     * @param iterable<string> $numbers
     * @return array<string,mixed>|null
     */
    public function setNumbers(string $locationId, string $connectorId, iterable $numbers): ?array
    {
        $others = array_values(array_filter($this->setup($locationId)['connector_mappings'] ?? [], static fn ($m) => ($m['connector_id'] ?? null) !== $connectorId));
        return $this->updateInsightsHubSetup($locationId, ['connector_mappings' => Mappings::edit($others, $connectorId, $numbers)]);
    }

    /**
     * Give groups Insights Hub access (read, merge, PATCH `group_access`).
     *
     * @param iterable<string> $groupIds
     * @return array<string,mixed>|null
     */
    public function addGroups(string $locationId, iterable $groupIds): ?array
    {
        return $this->updateInsightsHubSetup($locationId, ['group_access' => Mappings::merge($this->setup($locationId)['group_access'] ?? [], $groupIds)]);
    }

    /**
     * @param iterable<string> $groupIds
     * @return array<string,mixed>|null
     */
    public function removeGroups(string $locationId, iterable $groupIds): ?array
    {
        return $this->updateInsightsHubSetup($locationId, ['group_access' => Mappings::merge($this->setup($locationId)['group_access'] ?? [], [], $groupIds)]);
    }

    /** @return array<string,mixed> */
    private function setup(string $locationId): array
    {
        return Client::unwrap($this->getInsightsHubSetup($locationId)) ?? [];
    }
}
