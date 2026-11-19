<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Users operations, generated from openapi.yaml. Do not edit by hand. */
class UsersApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** List a company's users. `GET /companies/{companyId}/users` */
    public function listCompanyUsers(string $companyId, array $filters = []): Page
    {
        return $this->c->page('listCompanyUsers', ['companyId' => $companyId], $filters);
    }

    /** Every row of listCompanyUsers(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterCompanyUsers(string $companyId, array $filters = []): \Generator
    {
        return $this->c->iterate('listCompanyUsers', ['companyId' => $companyId], $filters);
    }

    /** Create a user. `POST /companies/{companyId}/users` @return array<string,mixed>|null */
    public function createCompanyUser(string $companyId, array $body): ?array
    {
        return $this->c->call('createCompanyUser', ['companyId' => $companyId], [], $body);
    }

    /** Read a user. `GET /companies/{companyId}/users/{userId}` @return array<string,mixed>|null */
    public function getCompanyUser(string $companyId, string $userId): ?array
    {
        return $this->c->call('getCompanyUser', ['companyId' => $companyId, 'userId' => $userId], [], null);
    }

    /** Update a user, role or locations. `PATCH /companies/{companyId}/users/{userId}` @return array<string,mixed>|null */
    public function updateCompanyUser(string $companyId, string $userId, array $body): ?array
    {
        return $this->c->call('updateCompanyUser', ['companyId' => $companyId, 'userId' => $userId], [], $body);
    }

    /** Deactivate a user. `POST /companies/{companyId}/users/{userId}/deactivate` @return array<string,mixed>|null */
    public function deactivateCompanyUser(string $companyId, string $userId): ?array
    {
        return $this->c->call('deactivateCompanyUser', ['companyId' => $companyId, 'userId' => $userId], [], null);
    }

    /** Reactivate a user. `POST /companies/{companyId}/users/{userId}/reactivate` @return array<string,mixed>|null */
    public function reactivateCompanyUser(string $companyId, string $userId): ?array
    {
        return $this->c->call('reactivateCompanyUser', ['companyId' => $companyId, 'userId' => $userId], [], null);
    }

    /** Resend the invitation email. `POST /companies/{companyId}/users/{userId}/resend-invitation` @return array<string,mixed>|null */
    public function resendInvitation(string $companyId, string $userId): ?array
    {
        return $this->c->call('resendInvitation', ['companyId' => $companyId, 'userId' => $userId], [], null);
    }

    /** Send a password reset email. `POST /companies/{companyId}/users/{userId}/reset-password` @return array<string,mixed>|null */
    public function resetPassword(string $companyId, string $userId): ?array
    {
        return $this->c->call('resetPassword', ['companyId' => $companyId, 'userId' => $userId], [], null);
    }

    /** Read a user's numbers. `GET /companies/{companyId}/users/{userId}/connector-mappings` @return array<string,mixed>|null */
    public function getUserNumberMappings(string $companyId, string $userId): ?array
    {
        return $this->c->call('getUserNumberMappings', ['companyId' => $companyId, 'userId' => $userId], [], null);
    }

    /** Set a user's numbers. `PUT /companies/{companyId}/users/{userId}/connector-mappings` @return array<string,mixed>|null */
    public function setUserNumberMappings(string $companyId, string $userId, array $body): ?array
    {
        return $this->c->call('setUserNumberMappings', ['companyId' => $companyId, 'userId' => $userId], [], $body);
    }

    /** List a location's users with products and groups. `GET /locations/{locationId}/users` */
    public function listLocationUsers(string $locationId, array $filters = []): Page
    {
        return $this->c->page('listLocationUsers', ['locationId' => $locationId], $filters);
    }

    /** Every row of listLocationUsers(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterLocationUsers(string $locationId, array $filters = []): \Generator
    {
        return $this->c->iterate('listLocationUsers', ['locationId' => $locationId], $filters);
    }

    /** Read a user at a location. `GET /locations/{locationId}/users/{userId}` @return array<string,mixed>|null */
    public function getLocationUser(string $locationId, string $userId): ?array
    {
        return $this->c->call('getLocationUser', ['locationId' => $locationId, 'userId' => $userId], [], null);
    }
}
