<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/** 429, after the SDK's own retries. `$retryAfter` is in seconds when the API sent it. */
class RateLimitError extends ApiError
{
    public function __construct(
        int $status,
        ?string $errorCode,
        string $message,
        public readonly ?int $retryAfter = null,
        ?string $requestId = null,
        ?string $operationId = null,
        bool $sdkText = false,
    ) {
        parent::__construct($status, $errorCode, $message, $requestId, $operationId, null, $sdkText);
    }
}
