<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/**
 * The integration user has MFA on, so no tokens were issued.
 * Turn MFA off for the integration user's company (`mfa_enforced: false`).
 */
class MfaRequiredError extends AuthError
{
    public const MESSAGE = 'Integration users need MFA off for their company. API keys arrive in December 2026.';

    public function __construct(?string $operationId = 'login')
    {
        parent::__construct(200, 'mfa_required', self::MESSAGE, null, $operationId, null, true);
    }

    protected function describe(?string $text): string
    {
        return (string) $text;
    }
}
