<?php

declare(strict_types=1);

namespace IntelligenceCloud\Lifecycle;

use IntelligenceCloud\Exception\FilterError;

/**
 * The Base Intelligence Seat set: two groups and two alert rules at one location.
 *
 * - Upset Customer: sentiment below -40, priority high.
 * - Customer Threat: sentiment below -20 AND any of the customer-threat phrases, priority critical.
 *   Customer Threat phrases are provided by your Tresic contact; pass them in.
 */
final class SeatDefaults
{
    public const MANAGER_ALERTS_GROUP = 'Manager Alerts';
    public const INSIGHTS_HUB_GROUP = 'Insights Hub Access';
    public const UPSET_CUSTOMER_RULE = 'Upset Customer';
    public const CUSTOMER_THREAT_RULE = 'Customer Threat';
    public const UPSET_CUSTOMER_THRESHOLD = -40;
    public const CUSTOMER_THREAT_THRESHOLD = -20;
    public const THREAT_PHRASES_MISSING = 'Customer Threat phrases are provided by your Tresic contact.';

    public const AFTER_CALL = 'after-call-copilot';
    public const FIRST_ALERT = 'first-alert-copilot';
    public const INSIGHTS_HUB = 'insights-hub';
    public const PRODUCTS = [self::AFTER_CALL, self::FIRST_ALERT, self::INSIGHTS_HUB];
    /** Company-user `department` that marks an integration (data-reading) user. */
    public const INTEGRATION_DEPARTMENT = 'integration';

    /**
     * @param list<string> $groupIds
     * @return array<string,mixed> rule body for createAlertRule
     */
    public static function upsetCustomerRule(array $groupIds, bool $enabled = false): array
    {
        return [
            'name' => self::UPSET_CUSTOMER_RULE, 'priority' => 'high', 'match_logic' => 'any', 'enabled' => $enabled,
            'triggers' => [['trigger_type' => 'sentiment', 'config' => ['operator' => 'lt', 'threshold' => self::UPSET_CUSTOMER_THRESHOLD]]],
            'group_ids' => array_values($groupIds),
        ];
    }

    /**
     * @param list<string> $groupIds
     * @param list<string> $phrases from your Tresic contact
     * @return array<string,mixed> rule body for createAlertRule
     */
    public static function customerThreatRule(array $groupIds, array $phrases, bool $enabled = false): array
    {
        if ($phrases === []) {
            throw new FilterError(self::THREAT_PHRASES_MISSING);
        }
        return [
            'name' => self::CUSTOMER_THREAT_RULE, 'priority' => 'critical', 'match_logic' => 'all', 'enabled' => $enabled,
            'triggers' => [
                ['trigger_type' => 'sentiment', 'config' => ['operator' => 'lt', 'threshold' => self::CUSTOMER_THREAT_THRESHOLD]],
                ['trigger_type' => 'keyword_match', 'config' => ['keywords' => array_values($phrases), 'match_logic' => 'any']],
            ],
            'group_ids' => array_values($groupIds),
        ];
    }
}
