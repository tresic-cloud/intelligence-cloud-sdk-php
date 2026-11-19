<?php

declare(strict_types=1);

namespace IntelligenceCloud\Tests\Unit;

use IntelligenceCloud\Client;
use IntelligenceCloud\Exception\ConflictError;
use IntelligenceCloud\Exception\ExtensionMappingError;
use IntelligenceCloud\Exception\FilterError;
use IntelligenceCloud\Exception\ValidationError;
use IntelligenceCloud\Lifecycle\ConnectorSpec;
use IntelligenceCloud\Lifecycle\LocationSpec;
use IntelligenceCloud\Lifecycle\SeatCustomer;
use IntelligenceCloud\Lifecycle\SeatCustomerSpec;
use IntelligenceCloud\Lifecycle\UserSpec;
use IntelligenceCloud\Tests\Unit\Support\FakeServer;
use IntelligenceCloud\Tests\Unit\Support\TestCase;

final class WorkflowsTest extends TestCase
{
    private const PHRASES = ['placeholder phrase one', 'placeholder phrase two'];

    private function spec(array $over = []): SeatCustomerSpec
    {
        $a = $over + [
            'companyName' => 'Riverbend Auto Group',
            'adminName' => 'Jordan Lee',
            'adminEmail' => 'jordan.lee@example.com',
            'adminPhone' => '+15555550142',
            'location' => new LocationSpec('Riverbend - Main', '100 River Rd', 'Springfield', 'IL', 'United States', '62701', 'csps-msps'),
            'mfaOff' => true,
            'threatPhrases' => self::PHRASES,
            'connector' => new ConnectorSpec('https://pbx.example.com/ns-api/v2', 'riverbend.example', 'k'),
        ];
        return new SeatCustomerSpec(...$a);
    }

    private static function user(string $name, string $email, bool $invite = false): UserSpec
    {
        return new UserSpec($email, $name, 'Test', sendInvitation: $invite);
    }

    /** @return array{0:FakeServer, 1:Client, 2:SeatCustomer} */
    private function provisioned(array $over = []): array
    {
        $s = new FakeServer();
        $ic = $this->faked($s);
        $c = $ic->workflows->provisionSeatCustomer($this->spec($over));
        $s->log = [];
        return [$s, $ic, $c];
    }

    private static function norm(array $lines): array
    {
        return array_map(static fn ($l) => preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-8[0-9a-f]{3}-[0-9a-f]{12}/', '{id}', $l), $lines);
    }

    public function testProvisionBuildsTheSeatSet(): void
    {
        $s = new FakeServer();
        $ic = $this->faked($s);
        $c = $ic->workflows->provisionSeatCustomer($this->spec(['ssoDomain' => 'riverbendauto.example.com']));

        self::assertSame(['company', 'location', 'group:Manager Alerts', 'group:Insights Hub Access', 'rule:Upset Customer', 'rule:Customer Threat', 'connector'], $c->created);
        self::assertSame('active', $c->connectorStatus);
        self::assertSame(['name' => '_ic-verify.riverbendauto.example.com', 'value' => 'abc123'], $c->ssoTxtRecord);
        self::assertFalse($s->companies[$c->companyId]['mfa_enforced']);

        $threat = $s->rules[$c->customerThreatRuleId];
        self::assertSame('critical', $threat['priority']);
        self::assertSame('all', $threat['match_logic']);
        self::assertFalse($threat['enabled']);
        self::assertFalse($s->rules[$c->upsetCustomerRuleId]['enabled']);
        self::assertSame(['operator' => 'lt', 'threshold' => -20], $threat['triggers'][0]['config']);
        self::assertSame(self::PHRASES, $threat['triggers'][1]['config']['keywords']);
        self::assertSame([['id' => $c->managerAlertsGroupId]], $threat['groups']);
        self::assertSame(['operator' => 'lt', 'threshold' => -40], $s->rules[$c->upsetCustomerRuleId]['triggers'][0]['config']);
        self::assertSame([$c->insightsHubGroupId], $s->ihSetup[$c->locationId]['group_access']);
        self::assertTrue($s->ihAll[$c->locationId]);

        // rules are created switched off and not switched on here (addManager / activateSeatAlerts do that)
        self::assertNotContains('PATCH /locations/{id}/first-alert/rules/{id}', self::norm($s->writes()));
    }

    public function testProvisionIsIdempotent(): void
    {
        [$s, $ic, $first] = $this->provisioned();
        $again = $ic->workflows->provisionSeatCustomer($this->spec());
        self::assertSame([], $s->writes(), 'a re-run writes nothing');
        self::assertSame([], $again->created);
        self::assertContains('connector', $again->existing);
        self::assertSame($first->customerThreatRuleId, $again->customerThreatRuleId);
    }

    public function testThreatPhrasesAreRequiredToCreateTheRule(): void
    {
        $s = new FakeServer();
        $ic = $this->faked($s);
        try {
            $ic->workflows->provisionSeatCustomer($this->spec(['threatPhrases' => null]));
            self::fail('expected FilterError');
        } catch (FilterError $e) {
            self::assertSame('Customer Threat phrases are provided by your Tresic contact.', $e->getMessage());
        }
        self::assertSame([], $s->companies, 'nothing is created without phrases');

        // An existing Customer Threat rule keeps its phrases on a re-run without them.
        [$s2, $ic2] = $this->provisioned();
        $again = $ic2->workflows->provisionSeatCustomer($this->spec(['threatPhrases' => null]));
        self::assertSame(self::PHRASES, $s2->rules[$again->customerThreatRuleId]['triggers'][1]['config']['keywords']);
    }

    public function testNewPhrasesUpdateTheRule(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $ic->workflows->provisionSeatCustomer($this->spec(['threatPhrases' => ['placeholder phrase three']]));
        self::assertSame(['placeholder phrase three'], $s->rules[$c->customerThreatRuleId]['triggers'][1]['config']['keywords']);
    }

    public function testConnectorErrorIsRecordedNotThrown(): void
    {
        $s = new FakeServer();
        $s->connectorFinal = 'error';
        $s->connectorStateReason = 'invalid_permissions';
        $ic = $this->faked($s);
        $c = $ic->workflows->provisionSeatCustomer($this->spec());
        self::assertSame('error', $c->connectorStatus);
        self::assertSame('invalid_permissions', $c->connectorStateReason);
        self::assertNotNull($c->connectorId);
        self::assertContains('connector', $c->created);
        self::assertNotNull($c->customerThreatRuleId, 'the rest is still done');
    }

    public function testInactiveCompanyNeedsReactivate(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $s->companies[$c->companyId]['is_active'] = false;
        try {
            $ic->workflows->provisionSeatCustomer($this->spec());
            self::fail('expected FilterError');
        } catch (FilterError $e) {
            self::assertStringContainsString('reactivate', $e->getMessage());
        }
        $s->log = [];
        $ic->workflows->provisionSeatCustomer($this->spec(['reactivate' => true]));
        self::assertContains('POST /companies/{id}/reactivate', self::norm($s->writes()));
    }

    public function testGetSeatCustomerRebuildsIds(): void
    {
        [, $ic, $c] = $this->provisioned();
        $again = $ic->workflows->getSeatCustomer($c->companyId);
        foreach (['locationId', 'managerAlertsGroupId', 'insightsHubGroupId', 'upsetCustomerRuleId', 'customerThreatRuleId', 'connectorId', 'connectorStatus'] as $k) {
            self::assertSame($c->{$k}, $again->{$k}, $k);
        }
    }

    public function testAddFrontlineUserOrder(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $mgr = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));
        $s->log = [];
        $u = $ic->workflows->addFrontlineUser($c, self::user('Sam', 'sam@example.com'), '2101');

        self::assertSame([
            'POST /companies/{id}/users',
            'POST /locations/{id}/users/{id}/products/after-call-copilot/enable',
            'PUT /locations/{id}/users/{id}/first-alert/email-preferences',
            'PUT /companies/{id}/users/{id}/connector-mappings',
            'PUT /companies/{id}/users/{id}/connector-mappings',
            'PUT /locations/{id}/first-alert/config',
            'PATCH /locations/{id}/insights-hub/setup',
        ], self::norm($s->writes()));
        self::assertSame('frontline', $u->kind);
        self::assertTrue($u->created);
        self::assertSame(['after-call-copilot'], $s->users[$u->userId]['products']);
        self::assertSame(['masterEnabled' => false, 'alerts' => []], $s->emailPrefs[$u->userId]);
        self::assertSame([['connector_id' => $c->connectorId, 'monitored_numbers' => ['2101']]], $s->userMappings[$u->userId]);
        self::assertSame(['2101'], $s->userMappings[$mgr->userId][0]['monitored_numbers']);
        self::assertSame(['2101'], $s->faConfig[$c->locationId]['connector_mappings'][0]['monitored_numbers']);
        self::assertSame(['2101'], $s->ihSetup[$c->locationId]['connector_mappings'][0]['monitored_numbers']);

        // re-run finds the user by email
        $again = $ic->workflows->addFrontlineUser($c, self::user('Sam', 'sam@example.com'), '2101');
        self::assertFalse($again->created);
        self::assertSame($u->userId, $again->userId);
    }

    public function testRefusedMappingStopsBeforeManagersFirstAlertAndInsightsHub(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $s->connectors[$c->connectorId]['status'] = 'error';
        try {
            $ic->workflows->addFrontlineUser($c, self::user('Sam', 'sam@example.com'), '2101');
            self::fail('expected ExtensionMappingError');
        } catch (ExtensionMappingError $e) {
            self::assertNotNull($e->userId);
            self::assertInstanceOf(ValidationError::class, $e->cause());
        }
        self::assertSame([], $s->faConfig[$c->locationId]['connector_mappings']);
        self::assertSame([], $s->ihSetup[$c->locationId]['connector_mappings']);
    }

    public function testManagerMappingIsExactlyTheEndUserLines(): void
    {
        // Base Intelligence Seat: a manager's own phone line is never captured.
        [$s, $ic, $c] = $this->provisioned();
        $ic->workflows->addFrontlineUser($c, self::user('A', 'a@example.com'), '2101');
        $ic->workflows->addFrontlineUser($c, self::user('B', 'b@example.com'), '2102');
        try {
            // @phpstan-ignore-next-line
            $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'), '2100');
            self::fail('expected a TypeError: addManager takes no extension');
        } catch (\TypeError) {
            // the third parameter is the bool $activateAlerts: an extension is refused before any write
        }
        $m = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));

        self::assertSame(['2101', '2102'], $m->numbers);
        self::assertSame(['2101', '2102'], $s->userMappings[$m->userId][0]['monitored_numbers']);
        self::assertCount(1, $s->userMappings[$m->userId]);
        self::assertSame('manager', $m->kind);
        self::assertSame(['2101', '2102'], $s->faConfig[$c->locationId]['connector_mappings'][0]['monitored_numbers']);
        self::assertSame(['2101', '2102'], $s->ihSetup[$c->locationId]['connector_mappings'][0]['monitored_numbers']);
        self::assertEqualsCanonicalizing(['after-call-copilot', 'first-alert-copilot', 'insights-hub'], $s->users[$m->userId]['products']);
        foreach ([$c->managerAlertsGroupId, $c->insightsHubGroupId] as $g) {
            self::assertContains($m->userId, array_column($s->groups[$g]['members'], 'id'));
        }
        self::assertTrue($s->emailPrefs[$m->userId]['masterEnabled']);
        self::assertEqualsCanonicalizing([$c->upsetCustomerRuleId, $c->customerThreatRuleId], array_column($s->emailPrefs[$m->userId]['alerts'], 'alertId'));

        // syncNumbers removes any number on a manager that is not a line
        $diff = $ic->workflows->syncNumbers($c, true);
        self::assertSame([], $diff['managers']);
        $ic->users->setNumbers($c->companyId, $m->userId, (string) $c->connectorId, ['2101', '2102', '2100']);
        $diff = $ic->workflows->syncNumbers($c);
        self::assertSame(['add' => [], 'remove' => ['2100']], $diff['managers'][$m->userId]);
        self::assertSame(['2101', '2102'], $s->userMappings[$m->userId][0]['monitored_numbers']);
        self::assertSame([$m->userId], array_column($ic->workflows->listManagers($c), 'id'));
        self::assertCount(2, $ic->workflows->listFrontlineUsers($c));
    }

    public function testChangeUserExtension(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $u = $ic->workflows->addFrontlineUser($c, self::user('A', 'a@example.com'), '2101');
        $m = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));
        $ic->workflows->changeUserExtension($c, $u->userId, '2101', '2106');
        self::assertSame(['2106'], $s->userMappings[$u->userId][0]['monitored_numbers']);
        self::assertSame(['2106'], $s->userMappings[$m->userId][0]['monitored_numbers']);
        self::assertSame(['2106'], $s->faConfig[$c->locationId]['connector_mappings'][0]['monitored_numbers']);
        self::assertSame(['2106'], $s->ihSetup[$c->locationId]['connector_mappings'][0]['monitored_numbers']);
    }

    public function testRemoveUserOrderAndCleanup(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $a = $ic->workflows->addFrontlineUser($c, self::user('A', 'a@example.com'), '2101');
        $ic->workflows->addFrontlineUser($c, self::user('B', 'b@example.com'), '2102');
        $m = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));

        $s->log = [];
        $r = $ic->workflows->removeUser($c, $a->userId);
        self::assertSame(['user_id' => $a->userId, 'released_numbers' => ['2101'], 'kind' => 'frontline'], $r);
        $w = self::norm($s->writes());
        self::assertSame('PUT /locations/{id}/users/{id}/group-membership', $w[0]);
        self::assertSame('PUT /companies/{id}/users/{id}/connector-mappings', $w[1]);
        self::assertSame('POST /companies/{id}/users/{id}/deactivate', end($w));
        self::assertSame('deactivated', $s->users[$a->userId]['status']);
        self::assertSame(['after-call-copilot'], $s->users[$a->userId]['products'], 'products are left as is');
        self::assertSame([], $s->userMappings[$a->userId]);
        self::assertSame(['2102'], $s->userMappings[$m->userId][0]['monitored_numbers']);
        self::assertSame(['2102'], $s->faConfig[$c->locationId]['connector_mappings'][0]['monitored_numbers']);
        self::assertSame(['2102'], $s->ihSetup[$c->locationId]['connector_mappings'][0]['monitored_numbers']);

        $rm = $ic->workflows->removeUser($c, $m->userId);
        self::assertSame('manager', $rm['kind']);
        self::assertSame(['2102'], $s->faConfig[$c->locationId]['connector_mappings'][0]['monitored_numbers'], 'location lines stay');
        foreach ($s->groups as $g) {
            self::assertNotContains($m->userId, array_column($g['members'], 'id'));
        }
        $ic->workflows->removeUser($c, $m->userId); // re-run is safe
    }

    public function testSyncNumbersDryRunAndApply(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $ic->workflows->addFrontlineUser($c, self::user('A', 'a@example.com'), '2101');
        $m = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));
        $s->faConfig[$c->locationId]['connector_mappings'][0]['monitored_numbers'][] = '2199';
        $s->userMappings[$m->userId] = [];

        $s->log = [];
        $dry = $ic->workflows->syncNumbers($c, true);
        self::assertSame([], $s->writes());
        self::assertSame(['2101'], $dry['lines']);
        self::assertSame(['add' => [], 'remove' => ['2199']], $dry['first_alert']);
        self::assertSame(['add' => ['2101'], 'remove' => []], $dry['managers'][$m->userId]);
        self::assertFalse($dry['applied']);

        self::assertTrue($ic->workflows->syncNumbers($c)['applied']);
        self::assertSame(['2101'], $s->faConfig[$c->locationId]['connector_mappings'][0]['monitored_numbers']);
        self::assertSame(['2101'], $s->userMappings[$m->userId][0]['monitored_numbers']);
        self::assertSame(['add' => [], 'remove' => []], $ic->workflows->syncNumbers($c, true)['first_alert']);
    }

    public function testNoConnector(): void
    {
        [, $ic, $c] = $this->provisioned(['connector' => null]);
        $this->expectException(FilterError::class);
        $ic->workflows->addFrontlineUser($c, self::user('A', 'a@example.com'), '2101');
    }

    // ------------------------------------------------------------ invitation and alert order

    public function testUsersAreCreatedWithoutAnInvitation(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $ic->workflows->addFrontlineUser($c, self::user('Sam', 'sam@example.com', true), '2101');
        $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com', true));
        $creates = [];
        foreach ($s->requests as $r) {
            if ($r->getMethod() === 'POST' && str_ends_with($r->getUri()->getPath(), '/users')) {
                $creates[] = json_decode((string) $r->getBody(), true);
            }
        }
        self::assertCount(2, $creates);
        foreach ($creates as $b) {
            self::assertFalse($b['send_invitation']);
        }
    }

    public function testFrontlineInvitationIsTheLastWrite(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $mgr = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));
        $s->log = [];
        $u = $ic->workflows->addFrontlineUser($c, self::user('Sam', 'sam@example.com', true), '2101');
        $w = $s->writes();
        self::assertSame("POST /companies/{$c->companyId}/users/{$u->userId}/resend-invitation", end($w));
        self::assertContains("PUT /companies/{$c->companyId}/users/{$u->userId}/connector-mappings", array_slice($w, 0, -1));
        self::assertContains("PUT /companies/{$c->companyId}/users/{$mgr->userId}/connector-mappings", array_slice($w, 0, -1));
        self::assertTrue($u->invited);
        self::assertSame([$u->userId], $s->invitations);
    }

    public function testManagerActivatesAlertsThenInvites(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $ic->workflows->addFrontlineUser($c, self::user('Sam', 'sam@example.com'), '2101');
        $s->log = [];
        $m = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com', true));
        $w = $s->writes();
        $mapping = array_search("PUT /companies/{$c->companyId}/users/{$m->userId}/connector-mappings", $w, true);
        $patches = array_keys(array_filter($w, static fn ($l) => str_starts_with($l, 'PATCH ') && str_contains($l, '/first-alert/rules/')));
        self::assertCount(2, $patches);
        self::assertLessThan($patches[0], $mapping);
        self::assertSame("POST /companies/{$c->companyId}/users/{$m->userId}/resend-invitation", end($w));
        self::assertTrue($s->rules[$c->upsetCustomerRuleId]['enabled']);
        self::assertTrue($s->rules[$c->customerThreatRuleId]['enabled']);
        self::assertEqualsCanonicalizing([$c->upsetCustomerRuleId, $c->customerThreatRuleId], $m->alertsActivated);
        self::assertTrue($m->invited);

        // a second manager finds the alerts on already
        $second = $ic->workflows->addManager($c, self::user('Lee', 'lee@example.com'));
        self::assertSame([], $second->alertsActivated);
        self::assertFalse($second->invited);
    }

    public function testManagerWithoutAlertActivation(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $m = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'), activateAlerts: false);
        self::assertSame([], $m->alertsActivated);
        self::assertFalse($s->rules[$c->upsetCustomerRuleId]['enabled']);
        self::assertFalse($s->rules[$c->customerThreatRuleId]['enabled']);
        self::assertCount(2, $ic->workflows->activateSeatAlerts($c));
        self::assertTrue($s->rules[$c->upsetCustomerRuleId]['enabled']);
        self::assertTrue($s->rules[$c->customerThreatRuleId]['enabled']);
        self::assertSame([], $ic->workflows->activateSeatAlerts($c));
    }

    public function testSendInvitationFalseNeverInvites(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $ic->workflows->addFrontlineUser($c, self::user('Sam', 'sam@example.com'), '2101');
        $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));
        self::assertSame([], $s->invitations);
        self::assertSame([], array_filter($s->log, static fn ($l) => str_ends_with($l, '/resend-invitation')));
    }

    public function testNoInvitationWhenMappingFails(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $s->connectors[$c->connectorId]['status'] = 'error';
        foreach ([['Sam', 'sam@example.com', '2101'], ['Ana', 'ana@example.com', '2103']] as [$n, $e, $x]) {
            try {
                $ic->workflows->addFrontlineUser($c, self::user($n, $e, true), $x);
                self::fail('expected ExtensionMappingError');
            } catch (ExtensionMappingError) {
            }
        }
        self::assertSame([], $s->invitations);
        // a manager whose lines cannot be mapped is not invited and does not switch the alerts on
        foreach (array_keys($s->users) as $uid) {
            $s->userMappings[$uid] = [['connector_id' => $c->connectorId, 'monitored_numbers' => ['2101']]];
            $s->users[$uid]['products'] = ['after-call-copilot'];
        }
        try {
            $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com', true));
            self::fail('expected ExtensionMappingError');
        } catch (ExtensionMappingError) {
        }
        self::assertSame([], $s->invitations);
        self::assertFalse($s->rules[$c->upsetCustomerRuleId]['enabled']);
        self::assertFalse($s->rules[$c->customerThreatRuleId]['enabled']);
    }

    public function testRerunAfterMappingFailureInvites(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $s->connectors[$c->connectorId]['status'] = 'error';
        try {
            $ic->workflows->addFrontlineUser($c, self::user('Sam', 'sam@example.com', true), '2101');
            self::fail('expected ExtensionMappingError');
        } catch (ExtensionMappingError $e) {
            $uid = $e->userId;
        }
        $s->connectors[$c->connectorId]['status'] = 'active'; // the connector is fixed
        $again = $ic->workflows->addFrontlineUser($c, self::user('Sam', 'sam@example.com', true), '2101');
        self::assertSame($uid, $again->userId);
        self::assertFalse($again->created);
        self::assertTrue($again->invited);
        self::assertSame([$again->userId], $s->invitations);
    }

    public function testRerunKeepsRulesOn(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));
        $ic->workflows->provisionSeatCustomer($this->spec());
        self::assertTrue($s->rules[$c->upsetCustomerRuleId]['enabled']);
        self::assertTrue($s->rules[$c->customerThreatRuleId]['enabled']);
    }

    // ------------------------------------------------------------ company matching (never adopt by name alone)

    public function testSameNameWithAnotherContactIsRefusedBeforeAnyWrite(): void
    {
        [$s, $ic] = $this->provisioned();
        $s->log = [];
        try {
            $ic->workflows->provisionSeatCustomer($this->spec(['adminEmail' => 'someone.else@example.com']));
            self::fail('expected ConflictError');
        } catch (ConflictError $e) {
            self::assertSame('company_name_conflict', $e->errorCode);
            self::assertStringContainsString('Pass companyId', $e->getMessage());
        }
        self::assertSame([], $s->writes(), 'no write before the refusal');
        self::assertCount(1, $s->companies);
    }

    public function testSameNameAndContactEmailIsReused(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $again = $ic->workflows->provisionSeatCustomer($this->spec(['adminEmail' => '  JORDAN.LEE@example.com ']));
        self::assertSame($c->companyId, $again->companyId);
        self::assertCount(1, $s->companies);
    }

    public function testExplicitCompanyIdWinsOverAnotherSameNamedCompany(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        // another reseller customer with the same name and a different contact
        $other = $s->id();
        $s->companies[$other] = ['id' => $other, 'company_name' => 'Riverbend Auto Group', 'primary_admin_contact_email' => 'other@example.com',
            'enabled_products' => [], 'is_active' => true, 'mfa_enforced' => true];
        $again = $ic->workflows->provisionSeatCustomer($this->spec(['companyId' => $c->companyId, 'adminEmail' => 'new.contact@example.com']));
        self::assertSame($c->companyId, $again->companyId);
        self::assertTrue($s->companies[$other]['mfa_enforced'], 'the other company is untouched');
        // without the id, two same-named companies: only the contact-email match is used
        $byEmail = $ic->workflows->provisionSeatCustomer($this->spec());
        self::assertSame($c->companyId, $byEmail->companyId);
    }

    // ------------------------------------------------------------ connector: domain match, fixed key

    public function testRerunFixesAConnectorInError(): void
    {
        $s = new FakeServer();
        $s->connectorFinal = 'error';
        $s->connectorStateReason = 'invalid_permissions';
        $ic = $this->faked($s);
        $c = $ic->workflows->provisionSeatCustomer($this->spec());
        self::assertSame('error', $c->connectorStatus);

        $s->connectorFinal = 'active';
        $s->log = [];
        $fixed = new ConnectorSpec('https://pbx.example.com/ns-api/v2', 'riverbend.example', 'new-key');
        $again = $ic->workflows->provisionSeatCustomer($this->spec(['connector' => $fixed]));
        self::assertSame($c->connectorId, $again->connectorId);
        self::assertSame('active', $again->connectorStatus);
        self::assertContains('connector:updated', $again->existing);
        self::assertSame('new-key', $s->connectorKeys[$c->connectorId]);
        $w = array_values(array_filter(self::norm($s->writes()), static fn ($l) => str_contains($l, '/connectors')));
        self::assertSame(['PATCH /locations/{id}/connectors/{id}', 'POST /locations/{id}/connectors/{id}/deactivate', 'POST /locations/{id}/connectors/{id}/reactivate'], $w);
        self::assertCount(1, $s->connectors);
    }

    public function testConnectorIsMatchedOnDomainNotName(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $renamed = new ConnectorSpec('https://pbx.example.com/ns-api/v2', 'RIVERBEND.example', 'k', 'Riverbend PBX');
        $again = $ic->workflows->provisionSeatCustomer($this->spec(['connector' => $renamed]));
        self::assertSame($c->connectorId, $again->connectorId);
        self::assertCount(1, $s->connectors, 'no second connector for the same domain');
        self::assertSame('Riverbend PBX', $s->connectors[$c->connectorId]['name']);
    }

    // ------------------------------------------------------------ integration users are never seats

    public function testIntegrationUserIsTaggedOnAllLinesAndNeverASeat(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $ic->workflows->addFrontlineUser($c, self::user('A', 'a@example.com'), '2101');
        $m = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));
        $i = $ic->workflows->addIntegrationUser($c, self::user('Sync', 'crm-sync@example.com'));
        self::assertSame('integration', $i->kind);
        self::assertSame('integration', $s->users[$i->userId]['department']);
        self::assertSame(['2101'], $s->userMappings[$i->userId][0]['monitored_numbers']);
        self::assertFalse($s->emailPrefs[$i->userId]['masterEnabled']);
        self::assertSame([$m->userId], array_column($ic->workflows->listManagers($c), 'id'));
        self::assertSame([$i->userId], array_column($ic->workflows->listIntegrationUsers($c), 'id'));
        self::assertNotContains($i->userId, array_column($ic->workflows->listFrontlineUsers($c), 'id'));

        // a new end user's line reaches the integration user too
        $ic->workflows->addFrontlineUser($c, self::user('B', 'b@example.com'), '2102');
        self::assertSame(['2101', '2102'], $s->userMappings[$i->userId][0]['monitored_numbers']);

        // syncNumbers keeps it on exactly the lines, and its numbers never become lines
        $s->userMappings[$i->userId] = [['connector_id' => $c->connectorId, 'monitored_numbers' => ['2101', '2102', '2999']]];
        $dry = $ic->workflows->syncNumbers($c, true);
        self::assertSame(['2101', '2102'], $dry['lines']);
        self::assertSame(['add' => [], 'remove' => ['2999']], $dry['integration_users'][$i->userId]);
        $ic->workflows->syncNumbers($c);
        self::assertSame(['2101', '2102'], $s->userMappings[$i->userId][0]['monitored_numbers']);

        $this->expectException(FilterError::class);
        $ic->workflows->addIntegrationUser($c, new UserSpec('sso-sync@example.com', 'S', 'S', authMethod: 'sso'));
    }

    public function testRemovingAnIntegrationUserKeepsEveryLine(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $ic->workflows->addFrontlineUser($c, self::user('A', 'a@example.com'), '2101');
        $ic->workflows->addFrontlineUser($c, self::user('B', 'b@example.com'), '2102');
        $m = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));
        $i = $ic->workflows->addIntegrationUser($c, self::user('Sync', 'crm-sync@example.com'));

        $r = $ic->workflows->removeUser($c, $i->userId);
        self::assertSame(['user_id' => $i->userId, 'released_numbers' => [], 'kind' => 'integration'], $r);
        $this->assertAllLinesKept($s, $c, $m->userId, ['2101', '2102']);
        self::assertSame('deactivated', $s->users[$i->userId]['status']);
    }

    public function testRemovingAnUntaggedAllLinesUserKeepsEveryLine(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $ic->workflows->addFrontlineUser($c, self::user('A', 'a@example.com'), '2101');
        $ic->workflows->addFrontlineUser($c, self::user('B', 'b@example.com'), '2102');
        $m = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));
        // a calls-only data user mapped to every line, without the integration tag
        $d = $ic->workflows->addFrontlineUser($c, self::user('Data', 'data@example.com'), '2101');
        $ic->users->setNumbers($c->companyId, $d->userId, $c->connectorId, ['2101', '2102']);
        self::assertSame(['2101', '2102'], $ic->workflows->syncNumbers($c, true)['lines'], 'a multi-line user adds no lines');

        $r = $ic->workflows->removeUser($c, $d->userId);
        self::assertSame('integration', $r['kind']);
        self::assertSame([], $r['released_numbers']);
        $this->assertAllLinesKept($s, $c, $m->userId, ['2101', '2102']);
    }

    public function testASharedLineIsNotReleased(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $a = $ic->workflows->addFrontlineUser($c, self::user('A', 'a@example.com'), '2101');
        $ic->workflows->addFrontlineUser($c, self::user('A2', 'a2@example.com'), '2101'); // a shared desk line
        $m = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));
        $r = $ic->workflows->removeUser($c, $a->userId);
        self::assertSame('frontline', $r['kind']);
        self::assertSame([], $r['released_numbers']);
        $this->assertAllLinesKept($s, $c, $m->userId, ['2101']);
    }

    public function testExcludedUsersAreInvisibleToTheWorkflows(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $ic->workflows->addFrontlineUser($c, self::user('A', 'a@example.com'), '2101');
        $x = $ic->workflows->addFrontlineUser($c, self::user('X', 'x@example.com'), '2109');
        $m = $ic->workflows->addManager($c, self::user('Pat', 'pat@example.com'));
        $c->excludeUserIds = [$m->userId];
        $c->excludeEmails = ['X@example.com'];
        self::assertSame([], $ic->workflows->listManagers($c));
        self::assertNotContains($x->userId, array_column($ic->workflows->listFrontlineUsers($c), 'id'));
        self::assertSame(['2101'], $ic->workflows->syncNumbers($c, true)['lines']);
    }

    // ------------------------------------------------------------ offboarding

    public function testOffboardCustomerOrder(): void
    {
        [$s, $ic, $c] = $this->provisioned();
        $ic->workflows->addFrontlineUser($c, self::user('A', 'a@example.com'), '2101');
        $i = $ic->workflows->addIntegrationUser($c, self::user('Sync', 'crm-sync@example.com'));
        $ic->intelligence->setAfterCallWebhook($c->locationId, 'https://hooks.example.com/ic/after-call');
        $ic->intelligence->setFirstAlertWebhook($c->locationId, 'https://hooks.example.com/ic/alerts');

        $s->log = [];
        $r = $ic->workflows->offboardCustomer($c);
        self::assertSame(['webhook_paused' => true, 'first_alert_webhook_cleared' => true, 'connector_deactivated' => true,
            'integration_users_deactivated' => [$i->userId], 'company_deactivated' => true], $r);
        self::assertSame([
            'PUT /locations/{id}/after-call/automation',
            'PUT /locations/{id}/first-alert/config',
            'POST /locations/{id}/connectors/{id}/deactivate',
            'POST /companies/{id}/users/{id}/deactivate',
            'POST /companies/{id}/deactivate',
        ], self::norm($s->writes()));
        self::assertFalse($s->acWebhooks[$c->locationId]['enabled']);
        self::assertSame('https://hooks.example.com/ic/after-call', $s->acWebhooks[$c->locationId]['webhook_url']);
        self::assertNull($s->faConfig[$c->locationId]['webhook_url']);
        self::assertSame(['2101'], $s->faConfig[$c->locationId]['connector_mappings'][0]['monitored_numbers'], 'numbers kept');
        self::assertSame('inactive', $s->connectors[$c->connectorId]['status']);
        self::assertFalse($s->companies[$c->companyId]['is_active']);

        $s->log = [];
        $again = $ic->workflows->offboardCustomer($c, false);
        self::assertFalse($again['webhook_paused']);
        self::assertFalse($again['connector_deactivated']);
        self::assertSame([], $s->writes(), 'a re-run writes nothing');
    }

    /** @param list<string> $lines */
    private function assertAllLinesKept(FakeServer $s, SeatCustomer $c, string $managerId, array $lines): void
    {
        self::assertSame($lines, $s->userMappings[$managerId][0]['monitored_numbers'], 'manager keeps every line');
        self::assertSame($lines, $s->faConfig[$c->locationId]['connector_mappings'][0]['monitored_numbers'], 'First Alert keeps every line');
        self::assertSame($lines, $s->ihSetup[$c->locationId]['connector_mappings'][0]['monitored_numbers'], 'Insights Hub keeps every line');
    }
}
