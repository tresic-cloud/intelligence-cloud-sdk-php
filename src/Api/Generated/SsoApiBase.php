<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Sso operations, generated from openapi.yaml. Do not edit by hand. */
class SsoApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** List claimed domains. `GET /companies/{companyId}/domains` @return array<string,mixed>|null */
    public function listCompanyDomains(string $companyId): ?array
    {
        return $this->c->call('listCompanyDomains', ['companyId' => $companyId], [], null);
    }

    /** Claim a domain. `POST /companies/{companyId}/domains` @return array<string,mixed>|null */
    public function claimCompanyDomain(string $companyId, array $body): ?array
    {
        return $this->c->call('claimCompanyDomain', ['companyId' => $companyId], [], $body);
    }

    /** Remove a domain claim. `DELETE /companies/{companyId}/domains/{domainId}` @return array<string,mixed>|null */
    public function removeCompanyDomain(string $companyId, string $domainId): ?array
    {
        return $this->c->call('removeCompanyDomain', ['companyId' => $companyId, 'domainId' => $domainId], [], null);
    }

    /** List verification attempts. `GET /companies/{companyId}/domains/{domainId}/verifications` @return array<string,mixed>|null */
    public function listDomainVerifications(string $companyId, string $domainId): ?array
    {
        return $this->c->call('listDomainVerifications', ['companyId' => $companyId, 'domainId' => $domainId], [], null);
    }

    /** Check the TXT record now. `POST /companies/{companyId}/domains/{domainId}/verifications` @return array<string,mixed>|null */
    public function verifyDomain(string $companyId, string $domainId): ?array
    {
        return $this->c->call('verifyDomain', ['companyId' => $companyId, 'domainId' => $domainId], [], null);
    }

    /** Read the identity provider connection. `GET /companies/{companyId}/identity-provider` @return array<string,mixed>|null */
    public function getIdentityProvider(string $companyId): ?array
    {
        return $this->c->call('getIdentityProvider', ['companyId' => $companyId], [], null);
    }

    /** Connect an OpenID Connect provider. `POST /companies/{companyId}/identity-provider` @return array<string,mixed>|null */
    public function connectIdentityProvider(string $companyId, array $body): ?array
    {
        return $this->c->call('connectIdentityProvider', ['companyId' => $companyId], [], $body);
    }

    /** Disconnect the identity provider. `DELETE /companies/{companyId}/identity-provider` @return array<string,mixed>|null */
    public function disconnectIdentityProvider(string $companyId): ?array
    {
        return $this->c->call('disconnectIdentityProvider', ['companyId' => $companyId], [], null);
    }

    /** Test the identity provider connection. `POST /companies/{companyId}/identity-provider/test` @return array<string,mixed>|null */
    public function testIdentityProvider(string $companyId): ?array
    {
        return $this->c->call('testIdentityProvider', ['companyId' => $companyId], [], null);
    }
}
