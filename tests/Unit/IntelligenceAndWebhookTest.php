<?php

declare(strict_types=1);

namespace IntelligenceCloud\Tests\Unit;

use IntelligenceCloud\Exception\FrontlineRequiredError;
use IntelligenceCloud\Exception\PayloadTooLargeError;
use IntelligenceCloud\Exception\WebhookPayloadError;
use IntelligenceCloud\Webhook\AfterCallEvent;
use IntelligenceCloud\Webhook\UnknownEvent;
use IntelligenceCloud\Tests\Unit\Support\TestCase;
use IntelligenceCloud\Webhook\Deduplicator;
use IntelligenceCloud\Webhook\PdoDedupStore;
use IntelligenceCloud\Webhook\Webhooks;

final class IntelligenceAndWebhookTest extends TestCase
{
    private const ALERT = '0193B6D0-2F3A-7B4C-9D5E-6F7A8B9C0D12';
    private const CALL = '0193b6d0-1e2f-7a3b-8c4d-5e6f7a8b9c01';

    public function testRequireFrontlineRefusesAdminLogins(): void
    {
        $ic = $this->scripted([self::login(), self::me('reseller_admin')]);
        try {
            $ic->intelligence->searchCalls(text: 'refund');
            self::fail();
        } catch (FrontlineRequiredError $e) {
            self::assertSame('reseller_admin', $e->role);
        }
        self::assertSame(['POST /auth/login', 'GET /me'], $this->sent());
    }

    public function testSearchCallsMapsPhoneToSearchAndCachesWhoami(): void
    {
        $page = fn () => self::json(200, ['data' => ['summary' => ['total_calls' => 0], 'calls' => []], 'meta' => ['has_more' => false]]);
        $ic = $this->scripted([self::login(), self::me(), $page(), $page()]);
        $ic->intelligence->searchCalls(phone: '+1 555 555 0142', status: 'completed', limit: 5);
        $ic->intelligence->searchCalls(text: 'loaner');
        parse_str($this->request(2)->getUri()->getQuery(), $q);
        self::assertSame(['search' => '+1 555 555 0142', 'status' => 'completed', 'limit' => '5'], $q);
        self::assertCount(4, $this->history);
    }

    public function testSearchAlertsMapsAlertNameToKeyword(): void
    {
        $ic = $this->scripted([self::login(), self::me(), self::json(200, ['data' => ['summary' => ['total' => 0], 'alerts' => []], 'meta' => []])]);
        $ic->intelligence->searchAlerts(alertName: 'Upset Customer', text: 'Morgan', sentiment: 'negative');
        parse_str($this->request(2)->getUri()->getQuery(), $q);
        self::assertSame(['keyword' => 'Upset Customer', 'search' => 'Morgan', 'sentiment' => 'negative'], $q);
    }

    public function testGetAlertSearchesByLowercaseIdAndAddsTheCall(): void
    {
        $ic = $this->scripted([
            self::login(), self::me(),
            self::json(200, ['data' => ['summary' => ['total' => 1], 'alerts' => [['id' => strtolower(self::ALERT), 'call_id' => self::CALL, 'keywords' => ['Customer Threat']]]], 'meta' => []]),
            self::json(200, ['data' => ['id' => self::CALL, 'summary' => 's']]),
        ]);
        $a = $ic->intelligence->getAlert(self::ALERT, withCall: true);
        parse_str($this->request(2)->getUri()->getQuery(), $q);
        self::assertSame(strtolower(self::ALERT), $q['search']);
        self::assertSame(['id' => self::CALL, 'summary' => 's'], $a['call']);
        self::assertCount(4, $this->history, 'one /me for the whole client');
    }

    public function testGetAlertReturnsNullWhenAbsent(): void
    {
        $ic = $this->scripted([self::login(), self::me(), self::json(200, ['data' => ['summary' => ['total' => 0], 'alerts' => []], 'meta' => []])]);
        self::assertNull($ic->intelligence->getAlert(self::ALERT));
    }

    public function testTranscriptLines(): void
    {
        $ic = $this->scripted([self::login(), self::me(), self::json(200, ['data' => ['transcript' => [['time' => 0.4, 'speaker' => 'Speaker 1', 'name' => 'Sam', 'text' => 'Hi', 'unexpected_field' => 'x']], 'unexpected_field' => 'x']])]);
        self::assertSame([['time' => 0.4, 'speaker' => 'Speaker 1', 'name' => 'Sam', 'text' => 'Hi']], $ic->intelligence->getTranscript(self::CALL));
    }

    public function testAfterCallWebhookConfig(): void
    {
        $ic = $this->scripted([self::login(), self::json(200, ['data' => null]), self::json(200, ['data' => ['id' => 'w', 'webhook_url' => 'https://hooks.example.com/x', 'enabled' => true]])]);
        self::assertNull($ic->intelligence->getAfterCallWebhook('0193b6c2-6b29-7a51-b0e4-1c8f3d5e7a02'));
        $ic->intelligence->setAfterCallWebhook('0193b6c2-6b29-7a51-b0e4-1c8f3d5e7a02', 'https://hooks.example.com/x');
        self::assertSame(['webhook_url' => 'https://hooks.example.com/x', 'enabled' => true], $this->bodyOf(2));
        self::assertSame('PUT', $this->request(2)->getMethod());
    }

    public function testWebhookTestsReturnOnlySuccessAndStatus(): void
    {
        $ic = $this->scripted([
            self::login(),
            self::json(200, ['data' => ['success' => false, 'http_status' => 500, 'response_time_ms' => 9, 'error' => 'raw text']]),
            self::json(200, ['data' => ['webhook_url' => 'https://hooks.example.com/a', 'connector_mappings' => []]]),
            self::json(200, ['data' => ['success' => true, 'status_code' => 200, 'message' => 'raw text']]),
        ]);
        $r = $ic->intelligence->testAfterCallWebhook('0193b6c2-6b29-7a51-b0e4-1c8f3d5e7a02');
        self::assertFalse($r->success);
        self::assertSame(500, $r->httpStatus);
        $r = $ic->intelligence->testFirstAlertWebhook('0193b6c2-6b29-7a51-b0e4-1c8f3d5e7a02');
        self::assertTrue($r->success);
        self::assertSame(200, $r->httpStatus);
        self::assertSame(['url' => 'https://hooks.example.com/a'], $this->bodyOf(3));
    }

    public function testWebhookUrlsMustBeHttps(): void
    {
        $ic = $this->scripted([]);
        $this->expectException(\IntelligenceCloud\Exception\FilterError::class);
        $ic->intelligence->setAfterCallWebhook('0193b6c2-6b29-7a51-b0e4-1c8f3d5e7a02', 'http://hooks.example.com/x');
    }

    // ------------------------------------------------------------ receiver

    private const EVENT = [
        'version' => '1.0', 'type' => 'end_of_call_summary', 'is_test' => false,
        'location_id' => '0193b6c2-6b29-7a51-b0e4-1c8f3d5e7a02', 'timestamp' => '2026-11-02T16:46:12Z',
        'call' => ['id' => self::CALL, 'caller_number' => '+15555550142', 'callee_number' => '2101', 'call_type' => 'inbound', 'duration_seconds' => 284, 'call_date' => '2026-11-02', 'platform' => 'netsapiens'],
        'summary' => ['subject' => 'flag-off field', 'text' => 'Car not ready.', 'sentiment' => 'negative'],
        'location' => ['id' => '0193b6c2-6b29-7a51-b0e4-1c8f3d5e7a02', 'company_id' => 'c', 'connector_id' => 'k'],
        'unknown_extra' => 'x',
    ];

    public function testParseAfterCallEvent(): void
    {
        $e = Webhooks::parseAfterCallEvent(json_encode(self::EVENT));
        self::assertSame(self::CALL, $e->callId);
        self::assertSame('inbound', $e->callType());
        self::assertSame(284, $e->durationSeconds());
        self::assertSame('Car not ready.', $e->summaryText());
        self::assertSame('negative', $e->sentiment());
        self::assertFalse($e->isTest);
        self::assertArrayNotHasKey('subject', $e->summary);
        self::assertArrayNotHasKey('unknown_extra', $e->raw);
    }

    public function testBadPayloads(): void
    {
        foreach (['not json', '[]', json_encode(['type' => 'end_of_call_summary', 'call' => []])] as $body) {
            try {
                Webhooks::parseAfterCallEvent($body);
                self::fail('expected WebhookPayloadError for ' . $body);
            } catch (WebhookPayloadError) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testDeduplicatorIsAnLru(): void
    {
        $d = new Deduplicator(null, 2);
        $e = Webhooks::parseAfterCallEvent(self::EVENT);
        self::assertTrue($d->isNew($e));
        self::assertTrue($d->isDuplicate($e));
        self::assertTrue($d->isNew('b'));
        self::assertTrue($d->isDuplicate('b'));   // touches 'b'
        self::assertTrue($d->isNew('c'));         // evicts the least recently used: the call id
        self::assertTrue($d->isDuplicate('b'));
        self::assertTrue($d->isNew(self::CALL));
    }

    public function testOtherEventTypesAreUnknownEventsNotErrors(): void
    {
        $e = Webhooks::parseAfterCallEvent(json_encode(['type' => 'something_new', 'version' => '2.0', 'id' => 'x']));
        self::assertInstanceOf(UnknownEvent::class, $e);
        self::assertSame('something_new', $e->type);
        self::assertSame('2.0', $e->version);
        $none = Webhooks::parseAfterCallEvent(json_encode(['hello' => 'world']));
        self::assertInstanceOf(UnknownEvent::class, $none);
        self::assertNull($none->type);
    }

    public function testBodySizeIsCapped(): void
    {
        self::assertSame(262144, Webhooks::MAX_EVENT_BYTES);
        $big = json_encode(['type' => 'end_of_call_summary', 'pad' => str_repeat('x', Webhooks::MAX_EVENT_BYTES)]);
        try {
            Webhooks::parseAfterCallEvent($big);
            self::fail('expected PayloadTooLargeError');
        } catch (PayloadTooLargeError) {
            self::addToAssertionCount(1);
        }
        $this->expectException(PayloadTooLargeError::class);
        Webhooks::parseAfterCallEvent(json_encode(self::EVENT), 100);
    }

    public function testSampleEventFileParses(): void
    {
        $e = Webhooks::parseAfterCallEvent((string) file_get_contents(__DIR__ . '/../../examples/after-call-sample.json'));
        self::assertInstanceOf(AfterCallEvent::class, $e);
        self::assertTrue($e->isTest);
    }

    public function testPdoDedupStoreIsSharedAndPersistent(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('pdo_sqlite is not available');
        }
        $file = tempnam(sys_get_temp_dir(), 'ic-dedup-');
        $a = new PdoDedupStore(new \PDO('sqlite:' . $file), 'ic_after_call_queue');
        $a->createTable();
        self::assertFalse((new Deduplicator($a))->isDuplicate(self::CALL));
        $b = new Deduplicator(new PdoDedupStore(new \PDO('sqlite:' . $file), 'ic_after_call_queue')); // another worker
        self::assertTrue($b->isDuplicate(self::CALL));
        @unlink($file);
    }
}
