<?php

declare(strict_types=1);

namespace IntelligenceCloud\Tests\Live;

use IntelligenceCloud\Client;

/**
 * Opt-in live tests: IC_LIVE=1. Point them at a test reseller, never at a customer.
 * Everything comes from the environment; nothing about the test tenant is written here.
 *
 * Environment:
 * - IC_RESELLER_USER / IC_RESELLER_PASSWORD: reseller admin integration user.
 * - IC_FRONTLINE_USER / IC_FRONTLINE_PASSWORD: frontline integration user, READ ONLY.
 * - SBX_NS_API_URL / SBX_NS_DOMAIN / SBX_NA_API_KEY: phone-system connector settings for the test company.
 * - IC_LIVE_COMPANY_NAME: the test company the lifecycle tests create (or find) and deactivate.
 * - IC_LIVE_COMPANY_CODE: its company code (optional).
 * - IC_LIVE_LOCATION_NAME: its location (default "SDK Test - Main").
 * - IC_LIVE_EMAIL_PREFIX / IC_LIVE_EMAIL_DOMAIN: test users are <prefix>-<n>@<domain>
 *   (n = 1 is the company's primary contact, who is always emailed an invitation).
 * - IC_LIVE_THREAT_PHRASES_FILE: Customer Threat phrases, one per line.
 * - IC_LIVE_EXPECT_CONNECTOR: the connector status the test key reaches, "active" or "error" (default "error").
 * - IC_LIVE_FREE_EXTENSION: an extension no other location monitors.
 * - IC_LIVE_CONFLICT_EXTENSION: an extension another location already monitors (optional).
 * - IC_LIVE_SEAT_COMPANY_ID: an existing Seat customer to read (optional).
 * - IC_LIVE_VERTICAL: vertical key for the location (default "csps-msps").
 *
 * Each credential signs in once per run (login is limited to 5 per minute per IP) and the
 * clients are shared by every test. The suite never calls logout. Assertions compare keys,
 * counts and ids only, so no call content can appear in a failure message.
 */
abstract class LiveTestCase extends \PHPUnit\Framework\TestCase
{
    private static ?Client $admin = null;
    private static ?Client $frontline = null;

    protected function setUp(): void
    {
        if (getenv('IC_LIVE') !== '1') {
            self::markTestSkipped('Live tests are opt-in: set IC_LIVE=1.');
        }
    }

    protected static function admin(): Client
    {
        return self::$admin ??= self::make('IC_RESELLER_USER', 'IC_RESELLER_PASSWORD');
    }

    protected static function frontline(): Client
    {
        return self::$frontline ??= self::make('IC_FRONTLINE_USER', 'IC_FRONTLINE_PASSWORD');
    }

    private static function make(string $userVar, string $passVar): Client
    {
        return new Client(email: self::requireEnv($userVar), password: self::requireEnv($passVar), userAgent: 'sdk-live-tests');
    }

    protected static function env(string $name): ?string
    {
        $v = getenv($name);
        return is_string($v) && $v !== '' ? $v : null;
    }

    protected static function requireEnv(string $name): string
    {
        $v = self::env($name);
        if ($v === null) {
            self::markTestSkipped("{$name} is not set.");
        }
        return $v;
    }

    protected static function testEmail(int $n): string
    {
        return sprintf('%s-%d@%s', self::requireEnv('IC_LIVE_EMAIL_PREFIX'), $n, self::requireEnv('IC_LIVE_EMAIL_DOMAIN'));
    }

    /** @param array<string,mixed> $row @param list<string> $allowed */
    protected static function assertOnlyKeys(array $row, array $allowed, string $what): void
    {
        $extra = array_diff(array_keys($row), $allowed);
        self::assertSame([], array_values($extra), "{$what} carries fields outside the published schema");
    }
}
