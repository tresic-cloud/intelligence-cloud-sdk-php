<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** AfterCallSettings operations, generated from openapi.yaml. Do not edit by hand. */
class AfterCallSettingsApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** Read a user's After Call settings. `GET /locations/{locationId}/users/{userId}/after-call-settings` @return array<string,mixed>|null */
    public function getUserAfterCallSettings(string $locationId, string $userId): ?array
    {
        return $this->c->call('getUserAfterCallSettings', ['locationId' => $locationId, 'userId' => $userId], [], null);
    }

    /** Update a user's After Call settings. `PATCH /locations/{locationId}/users/{userId}/after-call-settings` @return array<string,mixed>|null */
    public function updateUserAfterCallSettings(string $locationId, string $userId, array $body): ?array
    {
        return $this->c->call('updateUserAfterCallSettings', ['locationId' => $locationId, 'userId' => $userId], [], $body);
    }
}
