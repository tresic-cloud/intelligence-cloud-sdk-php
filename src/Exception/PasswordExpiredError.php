<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/**
 * Sign-in answered `password_expired: true`: the integration user's password must be changed.
 * A person signs in to the app as that user and sets a new password; then update your secret store.
 */
class PasswordExpiredError extends AuthError
{
    public const MESSAGE = 'The password has expired. Change it in the app, then sign in again.';

    public function __construct(?string $operationId = 'login')
    {
        parent::__construct(200, 'password_expired', self::MESSAGE, null, $operationId, null, true);
    }

    protected function describe(?string $text): string
    {
        return (string) $text;
    }
}
