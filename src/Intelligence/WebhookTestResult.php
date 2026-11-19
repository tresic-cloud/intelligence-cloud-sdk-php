<?php

declare(strict_types=1);

namespace IntelligenceCloud\Intelligence;

/** The outcome of a test send: whether the receiver answered 2xx, and its HTTP status. */
final class WebhookTestResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?int $httpStatus,
    ) {
    }
}
