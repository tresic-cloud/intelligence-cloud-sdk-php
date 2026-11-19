<?php

declare(strict_types=1);

namespace IntelligenceCloud\Tests\Unit;

use IntelligenceCloud\Auth\FileTokenStore;
use IntelligenceCloud\Auth\InMemoryTokenStore;
use IntelligenceCloud\Auth\Tokens;
use IntelligenceCloud\Auth\TokenStore;
use IntelligenceCloud\Exception\AuthError;
use IntelligenceCloud\Exception\MfaRequiredError;
use IntelligenceCloud\Tests\Unit\Support\TestCase;

final class AuthTest extends TestCase
{
    public function testSignsInOnFirstCallAndReusesTheToken(): void
    {
        $ic = $this->scripted([self::login(), self::json(200, ['data' => []]), self::json(200, ['data' => []])]);
        $ic->products->listProducts();
        $ic->products->listProducts();
        self::assertSame(['POST /auth/login', 'GET /products', 'GET /products'], $this->sent());
        self::assertSame('Bearer at1', $this->request(1)->getHeaderLine('Authorization'));
        self::assertSame(['email' => 'integration@example.com', 'password' => 'pw'], $this->bodyOf(0));
        self::assertStringStartsWith('intelligence-cloud-php/', $this->request(1)->getHeaderLine('User-Agent'));
    }

    public function testRefreshOn401OnceWithRotation(): void
    {
        $store = new RecordingStore();
        $ic = $this->scripted([
            self::login('at1', 'rt1'),
            self::json(401, ['statusCode' => 401, 'message' => 'Unauthorized']),
            self::login('at2', 'rt2'), // refresh response, same body as login
            self::json(200, ['data' => []]),
        ], $store);
        $ic->products->listProducts();

        self::assertSame(['POST /auth/login', 'GET /products', 'POST /auth/refresh', 'GET /products'], $this->sent());
        self::assertSame(['refresh_token' => 'rt1'], $this->bodyOf(2));
        self::assertSame('Bearer at2', $this->request(3)->getHeaderLine('Authorization'));
        // The spent token was marked before it was sent; the new pair was stored before use.
        self::assertSame([['at1', 'rt1'], ['at1', null], ['at2', 'rt2']], $store->writes);
        self::assertGreaterThanOrEqual(1, $store->locks);
    }

    public function testNeverSendsTheSameRefreshTokenTwice(): void
    {
        $ic = $this->scripted([
            self::login('at1', 'rt1'),
            self::json(401, []),
            self::login('at2', 'rt2'),
            self::json(200, ['data' => []]),
            self::json(401, []),
            self::login('at3', 'rt3'),
            self::json(200, ['data' => []]),
        ]);
        $ic->products->listProducts();
        $ic->products->listProducts();
        self::assertSame(['refresh_token' => 'rt1'], $this->bodyOf(2));
        self::assertSame(['refresh_token' => 'rt2'], $this->bodyOf(5));
    }

    public function testFailedRefreshSignsInAgain(): void
    {
        $ic = $this->scripted([
            self::login('at1', 'rt1'),
            self::json(401, []),
            self::json(401, ['error' => ['code' => 'renewal_replayed', 'message' => 'x']]),
            self::login('at9', 'rt9'),
            self::json(200, ['data' => []]),
        ]);
        $ic->products->listProducts();
        self::assertSame(['POST /auth/login', 'GET /products', 'POST /auth/refresh', 'POST /auth/login', 'GET /products'], $this->sent());
        self::assertSame('Bearer at9', $this->request(4)->getHeaderLine('Authorization'));
    }

    public function testSecond401IsAnAuthError(): void
    {
        $ic = $this->scripted([self::login(), self::json(401, []), self::login('at2', 'rt2'), self::json(401, ['statusCode' => 401, 'message' => 'Unauthorized'])]);
        $this->expectException(AuthError::class);
        $ic->products->listProducts();
    }

    public function testRenewsBeforeExpiry(): void
    {
        $store = new InMemoryTokenStore();
        $store->set(new Tokens('old', 'rt-old', time() + 10));
        $ic = $this->scripted([self::login('at2', 'rt2'), self::json(200, ['data' => []])], $store);
        $ic->products->listProducts();
        self::assertSame(['POST /auth/refresh', 'GET /products'], $this->sent());
        self::assertSame(['refresh_token' => 'rt-old'], $this->bodyOf(0));
    }

    public function testMfaRequired(): void
    {
        $ic = $this->scripted([self::json(200, ['status' => 'mfa_required', 'challenge_id' => 'c'])]);
        try {
            $ic->products->listProducts();
            self::fail('expected MfaRequiredError');
        } catch (MfaRequiredError $e) {
            self::assertSame('Integration users need MFA off for their company. API keys arrive in December 2026.', $e->getMessage());
        }
    }

    public function testLoginRateLimitWaitsSixtySecondsOnce(): void
    {
        $ic = $this->scripted([
            self::json(429, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'slow down']]),
            self::login(),
            self::json(200, ['data' => []]),
        ]);
        $ic->products->listProducts();
        self::assertSame([60.0], $this->sleeps);
        self::assertSame(['POST /auth/login', 'POST /auth/login', 'GET /products'], $this->sent());
    }

    public function testBadCredentials(): void
    {
        $ic = $this->scripted([self::json(401, ['error' => ['code' => 'invalid_credentials', 'message' => 'Invalid email or password']])]);
        try {
            $ic->products->listProducts();
            self::fail('expected AuthError');
        } catch (AuthError $e) {
            self::assertSame(401, $e->status);
            self::assertSame('invalid_credentials', $e->errorCode);
        }
    }

    public function testLogoutIsExplicitAndClearsTokens(): void
    {
        $store = new InMemoryTokenStore();
        $ic = $this->scripted([self::login(), self::json(200, ['message' => 'Logged out successfully'])], $store);
        $ic->logout();
        self::assertSame(['POST /auth/login', 'POST /auth/logout'], $this->sent());
        self::assertNull($store->get());
    }

    public function testCloseForgetsTokensWithoutSigningOut(): void
    {
        $store = new InMemoryTokenStore();
        $ic = $this->scripted([self::login(), self::json(200, ['data' => []])], $store);
        $ic->products->listProducts();
        $ic->close();
        self::assertNull($store->get());
        self::assertNotContains('POST /auth/logout', $this->sent());
    }

    public function testFileTokenStoreSharesOneSignIn(): void
    {
        $path = sys_get_temp_dir() . '/ic-tokens-' . bin2hex(random_bytes(4)) . '.json';
        try {
            $a = $this->scripted([self::login('shared', 'rt1'), self::json(200, ['data' => []])], new FileTokenStore($path));
            $a->products->listProducts();
            self::assertSame(0600, fileperms($path) & 0777);

            $b = $this->scripted([self::json(200, ['data' => []])], new FileTokenStore($path));
            $b->products->listProducts();
            self::assertSame(['GET /products'], $this->sent());
            self::assertSame('Bearer shared', $this->request(0)->getHeaderLine('Authorization'));
        } finally {
            @unlink($path);
            @unlink($path . '.lock');
        }
    }

    public function testSecretsStayOutOfDumps(): void
    {
        $ic = $this->scripted([]);
        $dump = print_r($ic, true);
        self::assertStringNotContainsString("'pw'", var_export($ic->__debugInfo(), true));
        self::assertStringNotContainsString('rt-secret', print_r(new Tokens('at-secret', 'rt-secret', 1), true));
        self::assertStringNotContainsString('[password] => pw', $dump);
    }
}

/** Records every write and lock, to check rotation order. */
final class RecordingStore implements TokenStore
{
    /** @var list<array{0:string, 1:?string}> */
    public array $writes = [];
    public int $locks = 0;
    private ?Tokens $t = null;

    public function get(): ?Tokens
    {
        return $this->t;
    }

    public function set(?Tokens $tokens): void
    {
        $this->t = $tokens;
        if ($tokens !== null) {
            $this->writes[] = [$tokens->accessToken, $tokens->refreshToken];
        }
    }

    public function withLock(callable $fn): mixed
    {
        $this->locks++;
        return $fn();
    }
}
