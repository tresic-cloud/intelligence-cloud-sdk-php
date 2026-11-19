<?php

declare(strict_types=1);

namespace IntelligenceCloud\Intelligence;

/**
 * A signed link to the call's audio (WAV). Fetch it with no Authorization header; Range requests
 * work. It expires 120 minutes after issue. Anyone holding it can download the audio: never log it.
 */
final class RecordingLink
{
    public function __construct(
        #[\SensitiveParameter] public readonly string $url,
        public readonly ?\DateTimeImmutable $expiresAt,
    ) {
    }

    /** @return array<string,string|null> */
    public function __debugInfo(): array
    {
        return ['url' => '<redacted>', 'expiresAt' => $this->expiresAt?->format(DATE_ATOM)];
    }
}
