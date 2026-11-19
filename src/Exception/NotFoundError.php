<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/** 404. Also returned for records outside your login's scope: treat as absent. */
class NotFoundError extends ApiError
{
    protected static function hint(): ?string
    {
        return 'Not found, or outside what this login can see.';
    }
}
