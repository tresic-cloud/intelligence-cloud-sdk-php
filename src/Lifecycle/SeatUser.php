<?php

declare(strict_types=1);

namespace IntelligenceCloud\Lifecycle;

/**
 * The result of addFrontlineUser / addManager / addIntegrationUser. `$kind` is "frontline", "manager" or "integration".
 * `$invited` is true when this call emailed the invitation; `$alertsActivated` lists the rule ids
 * this call switched on.
 */
final class SeatUser
{
    /**
     * @param list<string> $numbers
     * @param list<string> $alertsActivated
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $kind,
        public readonly array $numbers = [],
        public readonly bool $created = false,
        public readonly bool $invited = false,
        public readonly array $alertsActivated = [],
    ) {
    }
}
