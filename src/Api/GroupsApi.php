<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api;

use IntelligenceCloud\Api\Generated\GroupsApiBase;
use IntelligenceCloud\Client;

/** User group operations, plus read-merge-write membership helpers and a name lookup. */
final class GroupsApi extends GroupsApiBase
{
    /**
     * Add users to a group (read, merge, PATCH `member_ids`, which is a full replacement).
     *
     * @param iterable<string> $userIds
     * @return array<string,mixed>|null
     */
    public function addMembers(string $locationId, string $groupId, iterable $userIds): ?array
    {
        return $this->updateUserGroup($locationId, $groupId, ['member_ids' => Mappings::merge($this->memberIds($locationId, $groupId), $userIds)]);
    }

    /**
     * @param iterable<string> $userIds
     * @return array<string,mixed>|null
     */
    public function removeMembers(string $locationId, string $groupId, iterable $userIds): ?array
    {
        return $this->updateUserGroup($locationId, $groupId, ['member_ids' => Mappings::merge($this->memberIds($locationId, $groupId), [], $userIds)]);
    }

    /** @return list<string> the groups at the location the user belongs to */
    public function userGroupIds(string $locationId, string $userId): array
    {
        $out = [];
        foreach (Client::unwrap($this->getUserGroupMembership($locationId, $userId)) ?? [] as $r) {
            if (!empty($r['is_member'])) {
                $out[] = (string) $r['id'];
            }
        }
        return $out;
    }

    /**
     * Add one user to groups (read, merge, PUT the user's membership).
     *
     * @param iterable<string> $groupIds
     * @return array<string,mixed>|null
     */
    public function addUserToGroups(string $locationId, string $userId, iterable $groupIds): ?array
    {
        return $this->setUserGroupMembership($locationId, $userId, ['group_ids' => Mappings::merge($this->userGroupIds($locationId, $userId), $groupIds)]);
    }

    /**
     * Remove one user from the given groups, or from every group at the location (null).
     *
     * @param iterable<string>|null $groupIds
     * @return array<string,mixed>|null
     */
    public function removeUserFromGroups(string $locationId, string $userId, ?iterable $groupIds = null): ?array
    {
        $keep = $groupIds === null ? [] : Mappings::merge($this->userGroupIds($locationId, $userId), [], $groupIds);
        return $this->setUserGroupMembership($locationId, $userId, ['group_ids' => $keep]);
    }

    /** @return array<string,mixed>|null the group with exactly this name */
    public function findGroup(string $locationId, string $groupName): ?array
    {
        foreach ($this->iterUserGroups($locationId, ['search' => $groupName]) as $g) {
            if (($g['group_name'] ?? null) === $groupName) {
                return $g;
            }
        }
        return null;
    }

    /** @return list<string> */
    private function memberIds(string $locationId, string $groupId): array
    {
        $members = Client::unwrap($this->getUserGroup($locationId, $groupId))['members'] ?? [];
        return array_values(array_map(static fn ($m) => (string) $m['id'], $members));
    }
}
