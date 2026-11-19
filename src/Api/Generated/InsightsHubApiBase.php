<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** InsightsHub operations, generated from openapi.yaml. Do not edit by hand. */
class InsightsHubApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** Read Insights Hub setup. `GET /locations/{locationId}/insights-hub/setup` @return array<string,mixed>|null */
    public function getInsightsHubSetup(string $locationId): ?array
    {
        return $this->c->call('getInsightsHubSetup', ['locationId' => $locationId], [], null);
    }

    /** Save Insights Hub setup. `POST /locations/{locationId}/insights-hub/setup` @return array<string,mixed>|null */
    public function saveInsightsHubSetup(string $locationId, array $body): ?array
    {
        return $this->c->call('saveInsightsHubSetup', ['locationId' => $locationId], [], $body);
    }

    /** Update Insights Hub setup. `PATCH /locations/{locationId}/insights-hub/setup` @return array<string,mixed>|null */
    public function updateInsightsHubSetup(string $locationId, array $body): ?array
    {
        return $this->c->call('updateInsightsHubSetup', ['locationId' => $locationId], [], $body);
    }

    /** Read Insights Hub coverage. `GET /locations/{locationId}/insights-hub/config` @return array<string,mixed>|null */
    public function getInsightsHubConfig(string $locationId): ?array
    {
        return $this->c->call('getInsightsHubConfig', ['locationId' => $locationId], [], null);
    }

    /** Set Insights Hub coverage. `PATCH /locations/{locationId}/insights-hub/config` @return array<string,mixed>|null */
    public function updateInsightsHubConfig(string $locationId, array $body): ?array
    {
        return $this->c->call('updateInsightsHubConfig', ['locationId' => $locationId], [], $body);
    }

    /** Check a user's Insights Hub access. `GET /locations/{locationId}/users/{userId}/insights-hub/access` @return array<string,mixed>|null */
    public function getUserInsightsHubAccess(string $locationId, string $userId): ?array
    {
        return $this->c->call('getUserInsightsHubAccess', ['locationId' => $locationId, 'userId' => $userId], [], null);
    }
}
