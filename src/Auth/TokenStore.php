<?php

declare(strict_types=1);

namespace IntelligenceCloud\Auth;

/**
 * Where the client keeps its tokens.
 *
 * Refresh tokens are single use. The client marks a refresh token spent in the store before it
 * sends it, and stores the new pair before it uses the new access token, so a shared store never
 * hands out a spent refresh token.
 */
interface TokenStore
{
    public function get(): ?Tokens;

    public function set(?Tokens $tokens): void;

    /**
     * Run $fn while holding the store's lock, so only one caller signs in or renews at a time.
     *
     * @template T
     * @param callable():T $fn
     * @return T
     */
    public function withLock(callable $fn): mixed;
}
