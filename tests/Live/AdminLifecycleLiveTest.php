<?php

declare(strict_types=1);

namespace IntelligenceCloud\Tests\Live;

use IntelligenceCloud\Client;
use IntelligenceCloud\Exception\ConflictError;
use IntelligenceCloud\Exception\ExtensionMappingError;
use IntelligenceCloud\Exception\ValidationError;
use IntelligenceCloud\Lifecycle\ConnectorSpec;
use IntelligenceCloud\Lifecycle\LocationSpec;
use IntelligenceCloud\Lifecycle\SeatCustomer;
use IntelligenceCloud\Lifecycle\SeatCustomerSpec;
use IntelligenceCloud\Lifecycle\UserSpec;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Customer lifecycle against a test reseller, as reseller admin (see LiveTestCase for the environment).
 *
 * Reads: the existing Seat customer IC_LIVE_SEAT_COMPANY_ID. Writes: only in the company named
 * IC_LIVE_COMPANY_NAME, created on the first run and deactivated at the end of every run. Test
 * users are created with send_invitation=false; the platform always emails the company's primary
 * contact.
 *
 * IC_LIVE_EXPECT_CONNECTOR says which status the test key reaches: "error" (the default; the
 * connector ends in error/invalid_permissions and user number mappings are refused) or "active".
 */
final class AdminLifecycleLiveTest extends LiveTestCase
{
    /** @return list<string> */
    private static function phrases(): array
    {
        $lines = file(self::requireEnv('IC_LIVE_THREAT_PHRASES_FILE'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        return array_values(array_filter(array_map('trim', $lines), static fn ($l) => $l !== ''));
    }

    private static function expectConnector(): string
    {
        return self::env('IC_LIVE_EXPECT_CONNECTOR') ?? 'error';
    }

    private static ?SeatCustomer $customer = null;
    /** @var list<string> */
    private static array $users = [];

    // ------------------------------------------------------------ reads

    public function testReconstructsAnExistingSeatCustomer(): void
    {
        $ic = self::admin();
        $c = $ic->workflows->getSeatCustomer(self::requireEnv('IC_LIVE_SEAT_COMPANY_ID'));
        foreach ([$c->managerAlertsGroupId, $c->insightsHubGroupId, $c->upsetCustomerRuleId, $c->customerThreatRuleId] as $id) {
            self::assertNotNull($id);
        }
        $rule = Client::unwrap($ic->firstAlert->getAlertRule($c->locationId, (string) $c->upsetCustomerRuleId));
        self::assertSame(['operator' => 'lt', 'threshold' => -40], $rule['triggers'][0]['config']);

        $managers = $ic->workflows->listManagers($c);
        $frontline = $ic->workflows->listFrontlineUsers($c);
        self::assertSame([], array_values(array_intersect(array_column($managers, 'id'), array_column($frontline, 'id'))));
        foreach (array_merge($managers, $frontline) as $u) {
            self::assertNotSame('deactivated', $u['status']);
        }
        self::assertFalse($ic->workflows->syncNumbers($c, true)['applied']);
    }

    public function testConnectorResponsesAreProjected(): void
    {
        $ic = self::admin();
        $companyId = self::requireEnv('IC_LIVE_SEAT_COMPANY_ID');
        $c = $ic->workflows->getSeatCustomer($companyId);
        if ($c->connectorId === null) {
            self::markTestSkipped('The Seat customer has no connector.');
        }
        $con = Client::unwrap($ic->connectors->getConnector($c->locationId, $c->connectorId));
        self::assertSame(['api_url', 'domain'], array_keys($con['config']));
        self::assertArrayNotHasKey('subscription_id', $con);
        foreach ($ic->connectors->listCompanyConnectors($companyId, ['status' => $con['status']])->items as $row) {
            self::assertSame($con['status'], $row['status']);
            self::assertArrayNotHasKey('unmonitored', $row);
        }
    }

    public function testFindCompanyByName(): void
    {
        $ic = self::admin();
        $companyId = self::requireEnv('IC_LIVE_SEAT_COMPANY_ID');
        $name = Client::unwrap($ic->companies->getCompany($companyId))['company_name'];
        $rid = Client::unwrap($ic->account->getMe())['reseller_id'];
        self::assertSame($companyId, $ic->companies->findCompany($rid, $name)['id'] ?? null);
    }

    // ------------------------------------------------------------ lifecycle (writes in the SDK test company only)

    public function testProvisionSeatCustomer(): void
    {
        $ic = self::admin();
        $location = self::env('IC_LIVE_LOCATION_NAME') ?? 'SDK Test - Main';
        $spec = new SeatCustomerSpec(
            companyName: self::requireEnv('IC_LIVE_COMPANY_NAME'),
            adminName: 'SDK Test Admin',
            adminEmail: self::testEmail(1),
            adminPhone: '+15555550100',
            location: new LocationSpec($location, '1 Example Way', 'Springfield', 'IL', 'United States', '62701', self::env('IC_LIVE_VERTICAL') ?? 'csps-msps'),
            companyCode: self::env('IC_LIVE_COMPANY_CODE'),
            mfaOff: true,
            threatPhrases: self::phrases(),
            connector: new ConnectorSpec(self::requireEnv('SBX_NS_API_URL'), self::requireEnv('SBX_NS_DOMAIN'), self::requireEnv('SBX_NA_API_KEY'), str_replace(' - Main', ' - phone system', $location), 90.0),
            reactivate: true, // a previous run deactivated it
        );
        $c = $ic->workflows->provisionSeatCustomer($spec);
        self::$customer = $c;
        self::assertNotNull($c->connectorId);
        self::assertSame(self::expectConnector(), $c->connectorStatus);
        if (self::expectConnector() === 'error') {
            self::assertSame('invalid_permissions', $c->connectorStateReason);
        }

        self::assertFalse(Client::unwrap($ic->companies->getCompany($c->companyId))['mfa_enforced']);
        $rule = Client::unwrap($ic->firstAlert->getAlertRule($c->locationId, (string) $c->customerThreatRuleId));
        self::assertSame('all', $rule['match_logic']);
        if (in_array('rule:Customer Threat', $c->created, true)) {
            self::assertFalse($rule['enabled'], 'rules are created switched off');
        }
        $kw = array_values(array_filter($rule['triggers'], fn ($t) => $t['trigger_type'] === 'keyword_match'));
        self::assertEqualsCanonicalizing(self::phrases(), $kw[0]['config']['keywords']);
        self::assertTrue(Client::unwrap($ic->insightsHub->getInsightsHubConfig($c->locationId))['monitor_all_numbers']);

        // Re-running creates nothing new.
        $again = $ic->workflows->provisionSeatCustomer($spec);
        self::assertSame([], $again->created);
        self::assertSame($c->customerThreatRuleId, $again->customerThreatRuleId);
    }

    #[Depends('testProvisionSeatCustomer')]
    public function testAddFrontlineUserStopsAtTheRefusedMapping(): void
    {
        $ic = self::admin();
        $c = self::$customer;
        if (self::expectConnector() !== 'error') {
            self::markTestSkipped('The test key connects; see testMappingSuccessPath.');
        }
        $ext = self::requireEnv('IC_LIVE_FREE_EXTENSION');
        try {
            $ic->workflows->addFrontlineUser($c, new UserSpec(self::testEmail(2), 'SDK', 'Agent', sendInvitation: false), $ext);
            self::fail('user mappings need an active connector');
        } catch (ExtensionMappingError $e) {
            self::assertNotNull($e->userId);
            self::assertSame(422, $e->cause()->status);
            self::$users[] = (string) $e->userId;
        }
        $products = Client::unwrap($ic->users->getLocationUser($c->locationId, self::$users[0]))['enabled_products'];
        self::assertSame(['after-call-copilot'], $products);
        self::assertContains(self::$users[0], array_column($ic->workflows->listFrontlineUsers($c), 'id'));
        $fa = Client::unwrap($ic->firstAlert->getFirstAlertConfig($c->locationId));
        $numbers = [];
        foreach ($fa['connector_mappings'] ?? [] as $m) {
            $numbers = array_merge($numbers, $m['monitored_numbers']);
        }
        self::assertNotContains($ext, $numbers, 'nothing after the refused mapping was changed');
    }

    #[Depends('testProvisionSeatCustomer')]
    public function testAddManager(): void
    {
        $ic = self::admin();
        $c = self::$customer;
        $m = $ic->workflows->addManager($c, new UserSpec(self::testEmail(3), 'SDK', 'Manager', sendInvitation: false));
        self::$users[] = $m->userId;
        self::assertFalse($m->invited);
        foreach ([$c->upsetCustomerRuleId, $c->customerThreatRuleId] as $rid) {
            self::assertTrue(Client::unwrap($ic->firstAlert->getAlertRule($c->locationId, (string) $rid))['enabled'], 'switched on by addManager');
        }
        self::assertContains($m->userId, array_column($ic->workflows->listManagers($c), 'id'));
        self::assertTrue(Client::unwrap($ic->insightsHub->getUserInsightsHubAccess($c->locationId, $m->userId))['enabled']);
        $prefs = $ic->firstAlert->getAlertNotificationPreferences($c->locationId, $m->userId);
        self::assertTrue($prefs['emailNotifications']['masterEnabled']);
        self::assertCount(2, $prefs['emailNotifications']['alerts']);
        self::assertArrayNotHasKey('lastChangedBy', $prefs['emailNotifications']);
    }

    #[Depends('testAddManager')]
    public function testExtensionConflictTextIsReplaced(): void
    {
        $ext = self::requireEnv('IC_LIVE_CONFLICT_EXTENSION');
        $c = self::$customer;
        try {
            self::admin()->afterCallSettings->updateUserAfterCallSettings($c->locationId, (string) end(self::$users), [
                'connector_mappings' => [['connector_id' => $c->connectorId, 'monitored_numbers' => [$ext]]],
            ]);
            self::fail('expected a 409');
        } catch (ConflictError $e) {
            self::assertSame(ConflictError::EXTENSION_CONFLICT_MESSAGE, $e->getMessage());
            self::assertSame([$ext], $e->extensions);
        } catch (ValidationError $e) {
            self::markTestIncomplete('The API answered ' . $e->status . ' before the extension check.');
        }
    }

    #[Depends('testProvisionSeatCustomer')]
    public function testFirstAlertWebhookReadMergeWrite(): void
    {
        $ic = self::admin();
        $c = self::$customer;
        $ext = self::requireEnv('IC_LIVE_FREE_EXTENSION');
        $ic->firstAlert->addMonitoredNumbers($c->locationId, (string) $c->connectorId, [$ext]);
        $ic->intelligence->setFirstAlertWebhook($c->locationId, 'https://hooks.example.com/sdk-test/alerts');
        self::assertSame('https://hooks.example.com/sdk-test/alerts', $ic->intelligence->getFirstAlertWebhook($c->locationId));
        $ic->firstAlert->removeMonitoredNumbers($c->locationId, (string) $c->connectorId, [$ext]);
        self::assertSame('https://hooks.example.com/sdk-test/alerts', $ic->intelligence->getFirstAlertWebhook($c->locationId), 'a numbers change keeps the URL');
        $ic->intelligence->setFirstAlertWebhook($c->locationId, null);
        self::assertNull($ic->intelligence->getFirstAlertWebhook($c->locationId));
    }

    #[Depends('testAddManager')]
    public function testMappingSuccessPath(): void
    {
        if (self::expectConnector() !== 'active') {
            self::markTestSkipped('Needs a read/write phone-system key (IC_LIVE_EXPECT_CONNECTOR=active).');
        }
        $ic = self::admin();
        $c = self::$customer;
        $ext = self::requireEnv('IC_LIVE_FREE_EXTENSION');
        $u = $ic->workflows->addFrontlineUser($c, new UserSpec(self::testEmail(2), 'SDK', 'Agent', sendInvitation: false), $ext);
        self::$users[] = $u->userId;
        self::assertFalse($u->invited);
        self::assertSame([$ext], $ic->users->userNumbers($c->companyId, $u->userId, (string) $c->connectorId));
        self::assertContains($ext, $ic->users->userNumbers($c->companyId, self::$users[0], (string) $c->connectorId));
    }

    public static function tearDownAfterClass(): void
    {
        if (getenv('IC_LIVE') !== '1' || self::$customer === null) {
            return;
        }
        $ic = self::admin();
        $c = self::$customer;
        foreach (self::$users as $id) {
            try {
                $ic->workflows->removeUser($c, $id);
            } catch (\Throwable) {
            }
        }
        $steps = [
            fn () => $c->connectorId !== null ? $ic->connectors->deactivateConnector($c->locationId, $c->connectorId) : null,
            fn () => $ic->locations->deactivateLocation($c->companyId, $c->locationId),
            fn () => $ic->companies->deactivateCompany($c->companyId),
        ];
        foreach ($steps as $fn) {
            try {
                $fn();
            } catch (\Throwable) {
            }
        }
    }
}
