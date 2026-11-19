<?php

declare(strict_types=1);

namespace IntelligenceCloud\Lifecycle;

/**
 * What provisionSeatCustomer() builds.
 *
 * - `adminName`/`adminEmail`/`adminPhone`: the primary contact, invited by email as customer admin.
 * - `threatPhrases`: the Customer Threat phrases. Customer Threat phrases are provided by your
 *   Tresic contact; they are required to create the rule.
 * - `mfaOff`: true for a company whose users include an integration user.
 * - `reactivate`: reactivate a company or location found inactive (otherwise it is an error).
 * - `companyId`: the company to use, from a previous run's result. Store the returned companyId
 *   and pass it on every re-run. Without it, an existing company is reused only when both its name
 *   and its primary contact email match; a same-named company with another contact is refused
 *   with ConflictError (code `company_name_conflict`).
 */
final class SeatCustomerSpec
{
    /** @param list<string>|null $threatPhrases */
    public function __construct(
        public readonly string $companyName,
        public readonly string $adminName,
        public readonly string $adminEmail,
        public readonly string $adminPhone,
        public readonly LocationSpec $location,
        public readonly ?string $resellerId = null,
        public readonly ?string $companyCode = null,
        public readonly bool $mfaOff = false,
        public readonly ?array $threatPhrases = null,
        public readonly ?ConnectorSpec $connector = null,
        public readonly ?string $ssoDomain = null,
        public readonly bool $reactivate = false,
        public readonly ?string $companyId = null,
    ) {
    }
}
