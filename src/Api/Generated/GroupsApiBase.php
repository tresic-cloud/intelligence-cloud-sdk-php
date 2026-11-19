<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Groups operations, generated from openapi.yaml. Do not edit by hand. */
class GroupsApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** List a location's groups. `GET /locations/{locationId}/user-groups` */
    public function listUserGroups(string $locationId, array $filters = []): Page
    {
        return $this->c->page('listUserGroups', ['locationId' => $locationId], $filters);
    }

    /** Every row of listUserGroups(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterUserGroups(string $locationId, array $filters = []): \Generator
    {
        return $this->c->iterate('listUserGroups', ['locationId' => $locationId], $filters);
    }

    /** Create a group. `POST /locations/{locationId}/user-groups` @return array<string,mixed>|null */
    public function createUserGroup(string $locationId, array $body): ?array
    {
        return $this->c->call('createUserGroup', ['locationId' => $locationId], [], $body);
    }

    /** Read a group with its members. `GET /locations/{locationId}/user-groups/{groupId}` @return array<string,mixed>|null */
    public function getUserGroup(string $locationId, string $groupId): ?array
    {
        return $this->c->call('getUserGroup', ['locationId' => $locationId, 'groupId' => $groupId], [], null);
    }

    /** Rename a group or set its members. `PATCH /locations/{locationId}/user-groups/{groupId}` @return array<string,mixed>|null */
    public function updateUserGroup(string $locationId, string $groupId, array $body): ?array
    {
        return $this->c->call('updateUserGroup', ['locationId' => $locationId, 'groupId' => $groupId], [], $body);
    }

    /** Delete a group. `DELETE /locations/{locationId}/user-groups/{groupId}` @return array<string,mixed>|null */
    public function deleteUserGroup(string $locationId, string $groupId, array $filters = []): ?array
    {
        return $this->c->call('deleteUserGroup', ['locationId' => $locationId, 'groupId' => $groupId], $filters, null);
    }

    /** A user's groups at a location. `GET /locations/{locationId}/users/{userId}/group-membership` @return array<string,mixed>|null */
    public function getUserGroupMembership(string $locationId, string $userId): ?array
    {
        return $this->c->call('getUserGroupMembership', ['locationId' => $locationId, 'userId' => $userId], [], null);
    }

    /** Set a user's groups at a location. `PUT /locations/{locationId}/users/{userId}/group-membership` @return array<string,mixed>|null */
    public function setUserGroupMembership(string $locationId, string $userId, array $body): ?array
    {
        return $this->c->call('setUserGroupMembership', ['locationId' => $locationId, 'userId' => $userId], [], $body);
    }
}
