<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Companies operations, generated from openapi.yaml. Do not edit by hand. */
class CompaniesApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** List customer companies. `GET /resellers/{resellerId}/companies` */
    public function listCompanies(string $resellerId, array $filters = []): Page
    {
        return $this->c->page('listCompanies', ['resellerId' => $resellerId], $filters);
    }

    /** Every row of listCompanies(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterCompanies(string $resellerId, array $filters = []): \Generator
    {
        return $this->c->iterate('listCompanies', ['resellerId' => $resellerId], $filters);
    }

    /** Create a customer company. `POST /resellers/{resellerId}/companies` @return array<string,mixed>|null */
    public function createCompany(string $resellerId, array $body): ?array
    {
        return $this->c->call('createCompany', ['resellerId' => $resellerId], [], $body);
    }

    /** Read the MFA default for new companies. `GET /resellers/{resellerId}/companies/mfa` @return array<string,mixed>|null */
    public function getCompaniesMfaSummary(string $resellerId): ?array
    {
        return $this->c->call('getCompaniesMfaSummary', ['resellerId' => $resellerId], [], null);
    }

    /** Read a company. `GET /companies/{companyId}` @return array<string,mixed>|null */
    public function getCompany(string $companyId): ?array
    {
        return $this->c->call('getCompany', ['companyId' => $companyId], [], null);
    }

    /** Update a company. `PATCH /companies/{companyId}` @return array<string,mixed>|null */
    public function updateCompany(string $companyId, array $body): ?array
    {
        return $this->c->call('updateCompany', ['companyId' => $companyId], [], $body);
    }

    /** Deactivate a company. `POST /companies/{companyId}/deactivate` @return array<string,mixed>|null */
    public function deactivateCompany(string $companyId): ?array
    {
        return $this->c->call('deactivateCompany', ['companyId' => $companyId], [], null);
    }

    /** Reactivate a company. `POST /companies/{companyId}/reactivate` @return array<string,mixed>|null */
    public function reactivateCompany(string $companyId): ?array
    {
        return $this->c->call('reactivateCompany', ['companyId' => $companyId], [], null);
    }

    /** Read a company's security settings. `GET /companies/{companyId}/security-settings` @return array<string,mixed>|null */
    public function getCompanySecuritySettings(string $companyId): ?array
    {
        return $this->c->call('getCompanySecuritySettings', ['companyId' => $companyId], [], null);
    }

    /** Read company branding overrides. `GET /companies/{companyId}/branding` @return array<string,mixed>|null */
    public function getCompanyBranding(string $companyId): ?array
    {
        return $this->c->call('getCompanyBranding', ['companyId' => $companyId], [], null);
    }

    /** Update company branding overrides. `PATCH /companies/{companyId}/branding` @return array<string,mixed>|null */
    public function updateCompanyBranding(string $companyId, array $body): ?array
    {
        return $this->c->call('updateCompanyBranding', ['companyId' => $companyId], [], $body);
    }
}
