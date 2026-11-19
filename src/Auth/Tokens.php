<?php

declare(strict_types=1);

namespace IntelligenceCloud\Auth;

/** Access and refresh tokens. `$expiresAt` is a Unix timestamp (seconds). Never log these. */
final class Tokens
{
    public function __construct(
        public readonly string $accessToken,
        public readonly ?string $refreshToken,
        public readonly int $expiresAt,
    ) {
    }

    /** @return array{access_token:string, refresh_token:?string, expires_at:int} */
    public function toArray(): array
    {
        return ['access_token' => $this->accessToken, 'refresh_token' => $this->refreshToken, 'expires_at' => $this->expiresAt];
    }

    /** @param array<string,mixed> $a */
    public static function fromArray(array $a): ?self
    {
        if (!isset($a['access_token'], $a['expires_at']) || !is_string($a['access_token'])) {
            return null;
        }
        $rt = $a['refresh_token'] ?? null;
        return new self($a['access_token'], is_string($rt) ? $rt : null, (int) $a['expires_at']);
    }

    /** The refresh token has been sent once and must never be sent again. */
    public function withSpentRefreshToken(): self
    {
        return new self($this->accessToken, null, $this->expiresAt);
    }

    public function expiresWithin(int $seconds, ?int $now = null): bool
    {
        return $this->expiresAt - $seconds <= ($now ?? time());
    }

    /** @return array<string,string> */
    public function __debugInfo(): array
    {
        return ['accessToken' => '<redacted>', 'refreshToken' => '<redacted>', 'expiresAt' => (string) $this->expiresAt];
    }
}
