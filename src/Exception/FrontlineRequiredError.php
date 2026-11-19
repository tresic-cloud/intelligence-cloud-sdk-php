<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/** The login is not a frontline user, so conversation data would come back empty. */
class FrontlineRequiredError extends IntelligenceCloudError
{
    public function __construct(public readonly ?string $role)
    {
        parent::__construct(sprintf(
            'This login has role "%s". Conversation data endpoints return empty lists to admin logins: '
            . 'sign in with a frontline integration user.',
            $role ?? 'unknown',
        ));
    }
}
