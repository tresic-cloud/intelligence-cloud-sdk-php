<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api;

use IntelligenceCloud\Api\Generated\UsersApiBase;
use IntelligenceCloud\Client;
use IntelligenceCloud\Exception\ApiError;
use IntelligenceCloud\Exception\ValidationError;

/** User operations, plus lookups and read-merge-write helpers for locations and numbers. */
final class UsersApi extends UsersApiBase
{
    /**
     * Create a user. Always sends an Idempotency-Key (a new UUID unless you pass one), reused on
     * retries with `Accept-Encoding: identity`. A 422 can leave the user behind: the SDK then finds
     * the user by email and applies `location_ids` with PATCH. The one-time sign-in link the API
     * can return is never passed through.
     *
     * @param array<string,mixed> $body
     * @return array<string,mixed>|null
     */
    public function createCompanyUser(string $companyId, array $body, ?string $idempotencyKey = null): ?array
    {
        $key = $idempotencyKey ?? Client::uuid4();
        $email = isset($body['email']) && is_string($body['email']) ? $body['email'] : null;
        try {
            return $this->c->call('createCompanyUser', ['companyId' => $companyId], [], $body, [
                'headers' => ['Idempotency-Key' => $key],
                'retryHeaders' => ['Accept-Encoding' => 'identity'],
            ]);
        } catch (ValidationError $e) {
            if ($e->status !== 422 || $email === null) {
                throw $e;
            }
            $user = $this->findUserByEmail($companyId, $email);
            if ($user === null) {
                throw $e;
            }
            if (!empty($body['location_ids'])) {
                $this->updateCompanyUser($companyId, (string) $user['id'], ['location_ids' => array_values($body['location_ids'])]);
            }
            return $this->getCompanyUser($companyId, (string) $user['id']);
        } catch (ApiError $e) {
            // A replayed create can come back undecodable: the user exists, read it back.
            if ($e->status !== 0 || $email === null) {
                throw $e;
            }
            $user = $this->findUserByEmail($companyId, $email);
            if ($user === null) {
                throw $e;
            }
            return $this->getCompanyUser($companyId, (string) $user['id']);
        }
    }

    /** @return array<string,mixed>|null the company user with this email (case-insensitive) */
    public function findUserByEmail(string $companyId, string $email): ?array
    {
        $want = strtolower(trim($email));
        foreach ($this->iterCompanyUsers($companyId, ['search' => trim($email)]) as $u) {
            if (strtolower((string) ($u['email'] ?? '')) === $want) {
                return $u;
            }
        }
        return null;
    }

    /**
     * Assign the user to more locations (read, merge, PATCH `location_ids`).
     *
     * @param iterable<string> $locationIds
     * @return array<string,mixed>|null
     */
    public function addLocations(string $companyId, string $userId, iterable $locationIds): ?array
    {
        return $this->updateCompanyUser($companyId, $userId, ['location_ids' => Mappings::merge($this->locationIds($companyId, $userId), $locationIds)]);
    }

    /**
     * @param iterable<string> $locationIds
     * @return array<string,mixed>|null
     */
    public function removeLocations(string $companyId, string $userId, iterable $locationIds): ?array
    {
        return $this->updateCompanyUser($companyId, $userId, ['location_ids' => Mappings::merge($this->locationIds($companyId, $userId), [], $locationIds)]);
    }

    /** @return list<string> the user's mapped numbers (on one connector, or all) */
    public function userNumbers(string $companyId, string $userId, ?string $connectorId = null): array
    {
        return Mappings::numbersFor(Client::unwrap($this->getUserNumberMappings($companyId, $userId)) ?? [], $connectorId);
    }

    /**
     * Map numbers to the user (read, merge, PUT). The connector must be `active`.
     *
     * @param iterable<string> $numbers
     * @return array<string,mixed>|null
     */
    public function addNumbers(string $companyId, string $userId, string $connectorId, iterable $numbers): ?array
    {
        $cur = Client::unwrap($this->getUserNumberMappings($companyId, $userId)) ?? [];
        return $this->setUserNumberMappings($companyId, $userId, ['mappings' => Mappings::edit($cur, $connectorId, $numbers)]);
    }

    /**
     * @param iterable<string> $numbers
     * @return array<string,mixed>|null
     */
    public function removeNumbers(string $companyId, string $userId, string $connectorId, iterable $numbers): ?array
    {
        $cur = Client::unwrap($this->getUserNumberMappings($companyId, $userId)) ?? [];
        return $this->setUserNumberMappings($companyId, $userId, ['mappings' => Mappings::edit($cur, $connectorId, [], $numbers)]);
    }

    /**
     * Replace the user's numbers on one connector; other connectors are kept.
     *
     * @param iterable<string> $numbers
     * @return array<string,mixed>|null
     */
    public function setNumbers(string $companyId, string $userId, string $connectorId, iterable $numbers): ?array
    {
        $cur = Client::unwrap($this->getUserNumberMappings($companyId, $userId)) ?? [];
        $others = array_values(array_filter($cur, static fn ($m) => ($m['connector_id'] ?? null) !== $connectorId));
        return $this->setUserNumberMappings($companyId, $userId, ['mappings' => Mappings::edit($others, $connectorId, $numbers)]);
    }

    /** @return list<string> */
    private function locationIds(string $companyId, string $userId): array
    {
        $u = Client::unwrap($this->getCompanyUser($companyId, $userId)) ?? [];
        return array_values(array_map(static fn ($l) => (string) $l['location_id'], $u['locations'] ?? []));
    }
}
