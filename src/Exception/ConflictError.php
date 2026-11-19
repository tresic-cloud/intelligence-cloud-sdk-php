<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/**
 * 409: duplicate name or email, already active or inactive, or an extension already monitored
 * at another location.
 *
 * For an extension conflict the message is always EXTENSION_CONFLICT_MESSAGE and
 * `$extensions` lists the extensions that clashed. The API's own text is never passed on.
 */
class ConflictError extends ApiError
{
    public const EXTENSION_CONFLICT_MESSAGE = 'Extension is already monitored at another location.';
    /** provisionSeatCustomer found a same-named company with a different primary contact. */
    public const COMPANY_NAME_CONFLICT = 'company_name_conflict';

    /** @param list<string> $extensions */
    public function __construct(
        int $status,
        ?string $errorCode,
        string $message,
        ?string $requestId = null,
        ?string $operationId = null,
        public readonly array $extensions = [],
        ?\Throwable $previous = null,
        bool $sdkText = false,
    ) {
        parent::__construct($status, $errorCode, $message, $requestId, $operationId, $previous, $sdkText);
    }
}
