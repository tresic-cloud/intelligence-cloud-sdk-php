<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api;

use IntelligenceCloud\Api\Generated\CompaniesApiBase;
use IntelligenceCloud\Client;
use IntelligenceCloud\Exception\FilterError;

/** Companies operations, plus read-merge-write product helpers and a name lookup. */
final class CompaniesApi extends CompaniesApiBase
{
    /**
     * Switch products on for the company (read, merge, PATCH; `enabled_products` is a full replacement).
     *
     * @param iterable<string> $productKeys
     * @return array<string,mixed>|null
     */
    public function addProducts(string $companyId, iterable $productKeys): ?array
    {
        $cur = Client::unwrap($this->getCompany($companyId))['enabled_products'] ?? [];
        return $this->updateCompany($companyId, ['enabled_products' => Mappings::merge($cur, $productKeys)]);
    }

    /**
     * Switch products off for the company. At least one product must stay on.
     *
     * @param iterable<string> $productKeys
     * @return array<string,mixed>|null
     */
    public function removeProducts(string $companyId, iterable $productKeys): ?array
    {
        $cur = Client::unwrap($this->getCompany($companyId))['enabled_products'] ?? [];
        $keep = Mappings::merge($cur, [], $productKeys);
        if ($keep === []) {
            throw new FilterError('A company keeps at least one product; the API ignores an empty list.');
        }
        return $this->updateCompany($companyId, ['enabled_products' => $keep]);
    }

    /**
     * Every company under the reseller with exactly this name. Company names are not unique;
     * the primary contact email is.
     *
     * @return list<array<string,mixed>>
     */
    public function findCompanies(string $resellerId, string $companyName): array
    {
        $out = [];
        foreach ($this->iterCompanies($resellerId, ['search' => $companyName]) as $c) {
            if (($c['company_name'] ?? null) === $companyName) {
                $out[] = $c;
            }
        }
        return $out;
    }

    /**
     * The first company with exactly this name. Names are not unique: prefer findCompanies. (the API's search is a name prefix match), or null.
     *
     * @return array<string,mixed>|null
     */
    public function findCompany(string $resellerId, string $companyName): ?array
    {
        foreach ($this->iterCompanies($resellerId, ['search' => $companyName]) as $c) {
            if (($c['company_name'] ?? null) === $companyName) {
                return $c;
            }
        }
        return null;
    }
}
