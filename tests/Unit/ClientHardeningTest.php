<?php

declare(strict_types=1);

namespace IntelligenceCloud\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use IntelligenceCloud\Auth\InMemoryTokenStore;
use IntelligenceCloud\Auth\Tokens;
use IntelligenceCloud\Client;
use IntelligenceCloud\Exception\ApiError;
use IntelligenceCloud\Exception\FilterError;
use IntelligenceCloud\Exception\PasswordExpiredError;
use IntelligenceCloud\Exception\RateLimitError;
use IntelligenceCloud\Exception\ValidationError;
use IntelligenceCloud\Tests\Unit\Support\TestCase;

final class ClientHardeningTest extends TestCase
{
    public function testBaseUrlMustBeHttpsOutsideLocalhost(): void
    {
        foreach (['http://app.example.com/api/v1', 'ftp://app.example.com', 'app.example.com/api/v1'] as $url) {
            try {
                new Client('a@example.com', 'pw', $url);
                self::fail('expected FilterError for ' . $url);
            } catch (FilterError $e) {
                self::assertStringContainsString('https', $e->getMessage());
            }
        }
        foreach (['https://app.example.com/api/v1', 'http://localhost:8080/api/v1', 'http://127.0.0.1/api/v1', 'http://[::1]:9000/api/v1'] as $url) {
            self::assertInstanceOf(Client::class, new Client('a@example.com', 'pw', $url));
        }
    }

    public function testRedirectsAreNotFollowed(): void
    {
        $ic = $this->scripted([
            self::login(),
            new Response(302, ['Location' => 'https://elsewhere.example.com/steal']),
            self::json(200, ['data' => []]),
        ]);
        try {
            $ic->products->listProducts();
            self::fail('expected ApiError');
        } catch (ApiError $e) {
            self::assertSame(302, $e->status);
        }
        self::assertSame(['POST /auth/login', 'GET /products'], $this->sent());
        self::assertFalse($this->request(1)->getUri()->getHost() === 'elsewhere.example.com');
    }

    public function testLongRetryAfterIsNotWaitedOn(): void
    {
        $ic = $this->scripted([
            self::login(),
            self::json(429, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'x']], ['Retry-After' => '3600']),
        ]);
        try {
            $ic->products->listProducts();
            self::fail('expected RateLimitError');
        } catch (RateLimitError $e) {
            self::assertSame(3600, $e->retryAfter);
        }
        self::assertSame([], $this->sleeps);
    }

    public function testRefresh429RetriesTheSameRefreshTokenWithoutSigningIn(): void
    {
        $store = new InMemoryTokenStore();
        $store->set(new Tokens('old', 'rt-old', time() + 10));
        $ic = $this->scripted([
            self::json(429, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'x']], ['Retry-After' => '2']),
            self::login('at2', 'rt2'),
            self::json(200, ['data' => []]),
        ], $store);
        $ic->products->listProducts();
        self::assertSame(['POST /auth/refresh', 'POST /auth/refresh', 'GET /products'], $this->sent());
        self::assertSame(['refresh_token' => 'rt-old'], $this->bodyOf(0));
        self::assertSame(['refresh_token' => 'rt-old'], $this->bodyOf(1));
        self::assertSame([2.0], $this->sleeps);
    }

    public function testRefreshStill429RaisesAndKeepsTheRefreshToken(): void
    {
        $store = new InMemoryTokenStore();
        $store->set(new Tokens('old', 'rt-old', time() + 10));
        $r = fn () => self::json(429, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'x']], ['Retry-After' => '1']);
        $ic = $this->scripted([$r(), $r(), $r(), $r()], $store);
        try {
            $ic->products->listProducts();
            self::fail('expected RateLimitError');
        } catch (RateLimitError) {
            self::addToAssertionCount(1);
        }
        self::assertNotContains('POST /auth/login', $this->sent(), 'never signs in instead');
        self::assertSame('rt-old', $store->get()?->refreshToken, 'the unused refresh token is kept');
    }

    public function testLoginRateLimitWaitIsConfigurable(): void
    {
        $ic = $this->scripted([self::json(429, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'x']])], null, ['loginRateLimitWait' => 0.0]);
        $this->expectException(RateLimitError::class);
        try {
            $ic->products->listProducts();
        } finally {
            self::assertSame([], $this->sleeps);
        }
    }

    public function testPasswordExpired(): void
    {
        $ic = $this->scripted([self::json(200, ['access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => 899, 'password_expired' => true])]);
        try {
            $ic->products->listProducts();
            self::fail('expected PasswordExpiredError');
        } catch (PasswordExpiredError $e) {
            self::assertSame('The password has expired. Change it in the app, then sign in again.', $e->getMessage());
        }
    }

    public function testExceptionTextNeverCarriesTheServerMessage(): void
    {
        $ic = $this->scripted([self::login(), self::json(400, ['error' => ['code' => 'BAD_REQUEST', 'message' => 'number 5555550142 is invalid', 'request_id' => 'req-1']])]);
        try {
            $ic->products->listProducts();
            self::fail('expected ValidationError');
        } catch (ValidationError $e) {
            self::assertSame('HTTP 400 BAD_REQUEST (request_id req-1)', $e->getMessage());
            self::assertSame('number 5555550142 is invalid', $e->serverMessage);
            self::assertStringNotContainsString('5555550142', (string) $e);
        }
    }
}
