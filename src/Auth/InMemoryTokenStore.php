<?php

declare(strict_types=1);

namespace IntelligenceCloud\Auth;

/**
 * The default store: tokens live in this PHP process only. A PHP process runs one request at a
 * time, so the lock is process-local. Use FileTokenStore to share one sign-in between workers.
 */
final class InMemoryTokenStore implements TokenStore
{
    private ?Tokens $tokens = null;

    public function get(): ?Tokens
    {
        return $this->tokens;
    }

    public function set(?Tokens $tokens): void
    {
        $this->tokens = $tokens;
    }

    public function withLock(callable $fn): mixed
    {
        return $fn();
    }
}
