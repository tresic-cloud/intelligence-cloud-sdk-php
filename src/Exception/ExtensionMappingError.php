<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/**
 * A workflow created or updated the user but could not map the user's numbers. The usual cause
 * is a connector that is not `active` yet, or an extension another location already monitors.
 * Nothing after the refused mapping (managers, First Alert, Insights Hub) was changed. Fix the
 * cause, then run the workflow again (it finds the user and carries on) or call syncNumbers.
 */
class ExtensionMappingError extends IntelligenceCloudError
{
    public function __construct(public readonly ?string $userId, ApiError $cause)
    {
        parent::__construct(sprintf(
            'User %s: numbers not mapped (%s). Number mappings need an active connector and extensions no other location monitors.',
            $userId ?? '?',
            $cause->getMessage(),
        ), 0, $cause);
    }

    public function cause(): ApiError
    {
        /** @var ApiError */
        return $this->getPrevious();
    }
}
