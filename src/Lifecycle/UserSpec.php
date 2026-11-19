<?php

declare(strict_types=1);

namespace IntelligenceCloud\Lifecycle;

/**
 * A Seat user (end user or manager).
 *
 * `$sendInvitation`: email the invitation once the user is fully set up (lines mapped, groups and
 * products on). False: create the user without an invitation; send one later with
 * `$ic->users->resendInvitation()`.
 */
final class UserSpec
{
    public function __construct(
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $phoneNumber = null,
        public readonly bool $sendInvitation = true,
        public readonly string $authMethod = 'password',
    ) {
    }
}
