<?php

declare(strict_types=1);

namespace IntelligenceCloud\Lifecycle;

/**
 * A Base Intelligence Seat customer: every id the workflows need.
 *
 * A connector that ended in `error` is recorded here (`connectorStatus`, `connectorStateReason`,
 * e.g. `invalid_permissions` for a key without the permissions the connector needs), not thrown. `ssoTxtRecord` is the DNS TXT
 * record the customer publishes. `created` and `existing` list what this run made or found.
 */
final class SeatCustomer
{
    public ?string $managerAlertsGroupId = null;
    public ?string $insightsHubGroupId = null;
    public ?string $upsetCustomerRuleId = null;
    public ?string $customerThreatRuleId = null;
    public ?string $connectorId = null;
    public ?string $connectorStatus = null;
    public ?string $connectorStateReason = null;
    public ?string $ssoDomainId = null;
    /** @var array{name:?string, value:?string}|null */
    public ?array $ssoTxtRecord = null;
    /** @var list<string> */
    public array $created = [];
    /** @var list<string> */
    public array $existing = [];
    /**
     * Users every workflow ignores: not end users, managers or integration users, their numbers are
     * not location lines, and they are never updated.
     *
     * @var list<string>
     */
    public array $excludeUserIds = [];
    /** @var list<string> the same, by email (case-insensitive) */
    public array $excludeEmails = [];

    public function __construct(
        public readonly string $companyId,
        public readonly string $locationId,
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
