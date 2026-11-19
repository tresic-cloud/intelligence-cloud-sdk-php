<?php

declare(strict_types=1);

namespace IntelligenceCloud\Auth;

/**
 * Keeps tokens in a JSON file (mode 0600) and serialises sign-in and renewal across processes
 * with an exclusive flock on "<path>.lock". Use it when several PHP workers or cron jobs share
 * one integration user: two processes renewing with the same refresh token would end the
 * session everywhere.
 *
 * Put the file outside the web root.
 */
final class FileTokenStore implements TokenStore
{
    private int $depth = 0;

    /** @var resource|null */
    private $lockHandle = null;

    public function __construct(private readonly string $path)
    {
    }

    public function get(): ?Tokens
    {
        if (!is_file($this->path)) {
            return null;
        }
        $raw = @file_get_contents($this->path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? Tokens::fromArray($data) : null;
    }

    public function set(?Tokens $tokens): void
    {
        if ($tokens === null) {
            if (is_file($this->path)) {
                @unlink($this->path);
            }
            return;
        }
        $dir = dirname($this->path);
        $tmp = tempnam($dir, '.ic-tokens-');
        if ($tmp === false) {
            throw new \RuntimeException('Cannot write the token file.');
        }
        chmod($tmp, 0600);
        file_put_contents($tmp, json_encode($tokens->toArray(), JSON_THROW_ON_ERROR));
        if (!rename($tmp, $this->path)) {
            @unlink($tmp);
            throw new \RuntimeException('Cannot write the token file.');
        }
    }

    public function withLock(callable $fn): mixed
    {
        if ($this->depth > 0) {
            $this->depth++;
            try {
                return $fn();
            } finally {
                $this->depth--;
            }
        }
        $h = fopen($this->path . '.lock', 'c');
        if ($h === false) {
            throw new \RuntimeException('Cannot open the token lock file.');
        }
        @chmod($this->path . '.lock', 0600);
        flock($h, LOCK_EX);
        $this->lockHandle = $h;
        $this->depth = 1;
        try {
            return $fn();
        } finally {
            $this->depth = 0;
            flock($h, LOCK_UN);
            fclose($h);
            $this->lockHandle = null;
        }
    }
}
