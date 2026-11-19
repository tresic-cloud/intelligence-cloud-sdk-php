<?php

declare(strict_types=1);

namespace IntelligenceCloud\Webhook;

/**
 * A delivery whose `type` this SDK version does not handle. New event types can be added
 * without notice: answer 2xx and ignore it. `$raw` is the decoded body as received.
 */
final class UnknownEvent
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public readonly ?string $type,
        public readonly ?string $version,
        public readonly array $raw,
    ) {
    }
}
