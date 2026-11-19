<?php

declare(strict_types=1);

namespace IntelligenceCloud\Tests\Unit;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use IntelligenceCloud\Exception\ConnectorError;
use IntelligenceCloud\Exception\ConnectorTimeoutError;
use IntelligenceCloud\Exception\ValidationError;
use IntelligenceCloud\Tests\Unit\Support\TestCase;

final class UsersAndConnectorsTest extends TestCase
{
    private const CO = '0193b6c2-5a11-7c3e-8f20-6d4b9e1c2a77';
    private const LOC = '0193b6c2-6b29-7a51-b0e4-1c8f3d5e7a02';
    private const CON = '4b7e9d2c-1a3f-4e8b-9c6d-2f5a8e1b3c70';

    private const USER = ['email' => 'sam.ortiz@example.com', 'first_name' => 'Sam', 'last_name' => 'Ortiz', 'role' => 'frontline_user', 'location_ids' => [self::LOC], 'send_invitation' => false];

    public function testIdempotencyKeyIsSentAndReusedOnRetry(): void
    {
        $ic = $this->scripted([
            self::login(),
            new Response(502),
            self::json(201, ['data' => ['type' => 'company_user', 'attributes' => ['id' => 'u1', 'email' => self::USER['email']]]]),
        ]);
        $ic->users->createCompanyUser(self::CO, self::USER);
        $k1 = $this->request(1)->getHeaderLine('Idempotency-Key');
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $k1);
        self::assertSame($k1, $this->request(2)->getHeaderLine('Idempotency-Key'));
        self::assertSame('', $this->request(1)->getHeaderLine('Accept-Encoding'));
        self::assertSame('identity', $this->request(2)->getHeaderLine('Accept-Encoding'));
    }

    public function testCallerSuppliedIdempotencyKey(): void
    {
        $ic = $this->scripted([self::login(), self::json(201, ['data' => ['type' => 'company_user', 'attributes' => ['id' => 'u1']]])]);
        $ic->users->createCompanyUser(self::CO, self::USER, 'my-key-1');
        self::assertSame('my-key-1', $this->request(1)->getHeaderLine('Idempotency-Key'));
    }

    public function testHalfCreate422IsRecoveredByEmailLookupAndPatch(): void
    {
        $ic = $this->scripted([
            self::login(),
            self::json(422, ['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'One or more locations not found']]),
            self::json(200, ['data' => [['type' => 'company_user', 'attributes' => ['id' => 'u1', 'email' => 'SAM.ORTIZ@example.com']]], 'pagination' => ['has_more' => false]]),
            self::json(200, ['data' => ['type' => 'company_user', 'attributes' => ['id' => 'u1']]]),
            self::json(200, ['data' => ['type' => 'company_user', 'attributes' => ['id' => 'u1', 'status' => 'invited']]]),
        ]);
        $res = $ic->users->createCompanyUser(self::CO, self::USER);
        self::assertSame('u1', $res['data']['attributes']['id']);
        $sent = $this->sent();
        self::assertStringStartsWith('GET /companies/' . self::CO . '/users?', $sent[2]);
        self::assertStringContainsString('search=sam.ortiz%40example.com', $sent[2]);
        self::assertSame('PATCH /companies/' . self::CO . '/users/u1', $sent[3]);
        self::assertSame(['location_ids' => [self::LOC]], $this->bodyOf(3));
        self::assertSame('GET /companies/' . self::CO . '/users/u1', $sent[4]);
    }

    public function testGenuine422IsRaisedWhenNoUserWasLeftBehind(): void
    {
        $ic = $this->scripted([
            self::login(),
            self::json(422, ['error' => ['code' => 'ROLE_NOT_ASSIGNABLE', 'message' => 'role']]),
            self::json(200, ['data' => [], 'pagination' => ['has_more' => false]]),
        ]);
        $this->expectException(ValidationError::class);
        $ic->users->createCompanyUser(self::CO, self::USER);
    }

    public function testUndecodableReplayReadsTheUserBack(): void
    {
        $fail = fn () => new ConnectException('Error decoding body', new Request('POST', 'x'));
        $ic = $this->scripted([
            self::login(), $fail(), $fail(), $fail(), $fail(),
            self::json(200, ['data' => [['type' => 'company_user', 'attributes' => ['id' => 'u1', 'email' => self::USER['email']]]], 'pagination' => ['has_more' => false]]),
            self::json(200, ['data' => ['type' => 'company_user', 'attributes' => ['id' => 'u1']]]),
        ]);
        self::assertSame('u1', $ic->users->createCompanyUser(self::CO, self::USER)['data']['attributes']['id']);
    }

    // ------------------------------------------------------------ connectors

    private static function connector(string $status, array $extra = []): Response
    {
        return self::json($status === 'connecting' ? 202 : 200, ['data' => ['id' => self::CON, 'status' => $status, 'config' => ['api_url' => 'u', 'domain' => 'd', 'internal_ref' => 'redacted']] + $extra]);
    }

    public function testCreateChecksEveryTenSecondsUntilActive(): void
    {
        $ic = $this->scripted([self::login(), self::connector('connecting'), self::connector('connecting'), self::connector('active')]);
        $c = $ic->connectors->createConnector(self::LOC, ['name' => 'PBX', 'type' => 'netsapiens', 'config' => ['api_url' => 'https://pbx.example.com/ns-api/v2', 'domain' => 'd', 'api_key' => 'k']]);
        self::assertSame('active', $c['data']['status']);
        self::assertSame(['api_url' => 'u', 'domain' => 'd'], $c['data']['config']);
        self::assertSame('netsapiens', $this->bodyOf(1)['type']);
        self::assertSame([10.0], $this->sleeps);
    }

    public function testErrorStateRaisesConnectorError(): void
    {
        $ic = $this->scripted([self::login(), self::connector('connecting'), self::connector('error', ['state_reason' => 'invalid_permissions', 'status_message' => 'The key was refused.'])]);
        try {
            $ic->connectors->createConnector(self::LOC, ['name' => 'PBX', 'type' => 'netsapiens', 'config' => ['api_url' => 'a', 'domain' => 'd', 'api_key' => 'k']]);
            self::fail('expected ConnectorError');
        } catch (ConnectorError $e) {
            self::assertSame('invalid_permissions', $e->stateReason);
            self::assertSame('error', $e->connectorStatus);
            self::assertSame(self::CON, $e->connectorId);
            self::assertSame('The key was refused.', $e->statusMessage);
            self::assertArrayNotHasKey('internal_ref', $e->connector['config']);
        }
    }

    public function testWaitTimesOut(): void
    {
        $ic = $this->scripted([self::login(), self::connector('connecting'), self::connector('connecting'), self::connector('connecting'), self::connector('connecting')]);
        $this->expectException(ConnectorTimeoutError::class);
        $this->expectExceptionMessageMatches('/still "connecting"/');
        $ic->connectors->createConnector(self::LOC, ['name' => 'PBX', 'type' => 'netsapiens', 'config' => ['api_url' => 'a', 'domain' => 'd', 'api_key' => 'k']], true, 15.0);
    }

    public function testDeactivateWaitsForInactive(): void
    {
        $ic = $this->scripted([self::login(), self::connector('deactivating'), self::connector('deactivating'), self::connector('inactive')]);
        self::assertSame('inactive', $ic->connectors->deactivateConnector(self::LOC, self::CON)['data']['status']);
    }

    // ------------------------------------------------------------ read-merge-write helpers

    public function testAddMonitoredNumbersKeepsTheWebhookUrl(): void
    {
        $ic = $this->scripted([
            self::login(),
            self::json(200, ['data' => ['webhook_url' => 'https://hooks.example.com/a', 'unexpected_flag' => false, 'connector_mappings' => [['id' => 'm', 'connector_id' => self::CON, 'monitored_numbers' => ['2101']]]]]),
            self::json(200, ['data' => []]),
        ]);
        $ic->firstAlert->addMonitoredNumbers(self::LOC, self::CON, ['2102', '2101']);
        self::assertSame(['webhook_url' => 'https://hooks.example.com/a', 'connector_mappings' => [['connector_id' => self::CON, 'monitored_numbers' => ['2101', '2102']]]], $this->bodyOf(2));
    }

    public function testSetFirstAlertWebhookKeepsTheNumbers(): void
    {
        $ic = $this->scripted([
            self::login(),
            self::json(200, ['data' => ['webhook_url' => null, 'connector_mappings' => [['id' => 'm', 'connector_id' => self::CON, 'connector_name' => 'PBX', 'monitored_numbers' => ['2101']]]]]),
            self::json(200, ['data' => []]),
        ]);
        $ic->intelligence->setFirstAlertWebhook(self::LOC, 'https://hooks.example.com/alerts');
        self::assertSame(['webhook_url' => 'https://hooks.example.com/alerts', 'connector_mappings' => [['connector_id' => self::CON, 'monitored_numbers' => ['2101']]]], $this->bodyOf(2));
    }

    public function testAddGroupMembersMerges(): void
    {
        $ic = $this->scripted([
            self::login(),
            self::json(200, ['data' => ['type' => 'user_group', 'attributes' => ['id' => 'g', 'members' => [['id' => 'a']]]]]),
            self::json(200, ['data' => []]),
        ]);
        $ic->groups->addMembers(self::LOC, '0193b6c2-8d51-74e0-9b6a-2c7e1f3d5a10', ['b', 'a']);
        self::assertSame(['member_ids' => ['a', 'b']], $this->bodyOf(2));
    }

    public function testRecordingUrlReturnsOnlyTheLinkAndExpiry(): void
    {
        $body = ['data' => ['id' => 'c', 'summary' => 's',
            'vcon' => ['unexpected_id' => 'x', 'recording_url' => 'https://rec.example/x.wav?sig=abc', 'recording_url_expires_at' => '2026-11-02T18:00:00Z', 'recording_mime_type' => 'audio/wav', 'leg_count' => 1]]];
        $ic = $this->scripted([self::login(), self::me(), self::json(200, $body), self::json(200, $body)]);
        $rec = $ic->intelligence->getRecordingUrl('0193b6d0-1e2f-7a3b-8c4d-5e6f7a8b9c01');
        self::assertSame('https://rec.example/x.wav?sig=abc', $rec->url);
        self::assertSame('2026-11-02T18:00:00+00:00', $rec->expiresAt->format(DATE_ATOM));
        self::assertStringNotContainsString('sig=abc', print_r($rec, true));
        // getCall through intelligence never carries the recording block
        self::assertArrayNotHasKey('vcon', $ic->intelligence->getCall('0193b6d0-1e2f-7a3b-8c4d-5e6f7a8b9c01'));

        $ic = $this->scripted([self::login(), self::me(), self::json(200, ['data' => ['id' => 'c', 'vcon' => ['recording_url' => null]]])]);
        self::assertNull($ic->intelligence->getRecordingUrl('0193b6d0-1e2f-7a3b-8c4d-5e6f7a8b9c01'));
    }
}
