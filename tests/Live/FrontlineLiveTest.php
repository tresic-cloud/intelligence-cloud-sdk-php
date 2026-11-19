<?php

declare(strict_types=1);

namespace IntelligenceCloud\Tests\Live;

use IntelligenceCloud\Exception\FilterError;
use IntelligenceCloud\Exception\FrontlineRequiredError;

/** Read-only checks with a frontline data login. Shapes and counts only. */
final class FrontlineLiveTest extends LiveTestCase
{
    private const CALL_KEYS = ['id', 'location_id', 'call_date', 'caller_timezone', 'caller_name', 'caller_number', 'callee_name', 'callee_number',
        'call_type', 'status', 'sentiment', 'sentiment_score', 'sentiment_label', 'summary', 'top_highlight', 'highlights', 'duration', 'leg_count',
        'task_count', 'completed_task_count'];

    public function testWhoamiIsFrontline(): void
    {
        $me = self::frontline()->intelligence->requireFrontline();
        self::assertSame('frontline_user', $me['role']);
        self::assertOnlyKeys($me, ['id', 'email', 'first_name', 'last_name', 'display_name', 'user_type', 'role', 'status', 'scopes',
            'reseller_id', 'company_id', 'location_ids', 'enabled_products', 'mfa_available'], '/me');
        foreach (array_keys($me) as $k) {
            self::assertStringEndsNotWith('_enabled', $k);
        }
    }

    public function testAdminLoginIsRefusedForConversationData(): void
    {
        $this->expectException(FrontlineRequiredError::class);
        self::admin()->intelligence->searchCalls(limit: 1);
    }

    public function testVisibleLocations(): void
    {
        $page = self::frontline()->account->listVisibleLocations(['limit' => 5]);
        self::assertNotEmpty($page->items);
        self::assertArrayHasKey('location_name', $page->items[0]);
    }

    public function testSearchCallsAndPaging(): void
    {
        $ic = self::frontline();
        $page = $ic->intelligence->searchCalls(dateFrom: new \DateTimeImmutable('-7 days'), limit: 5);
        self::assertLessThanOrEqual(5, count($page));
        self::assertIsArray($page->summary);
        self::assertArrayHasKey('total_calls', $page->summary);
        if ($page->items === []) {
            self::markTestIncomplete('No calls in the last 7 days for this login.');
        }
        self::assertOnlyKeys($page->items[0], self::CALL_KEYS, 'call list row');

        $ids = [];
        foreach ($ic->intelligence->iterCalls(dateFrom: new \DateTimeImmutable('-7 days'), status: 'completed') as $row) {
            $ids[] = $row['id'];
            if (count($ids) >= 150) {
                break; // two pages of 100 is enough to prove cursor paging
            }
        }
        self::assertSame(count($ids), count(array_unique($ids)), 'paging returned duplicates');
    }

    public function testCallDetailTranscriptAndRecording(): void
    {
        $ic = self::frontline();
        $page = $ic->calls->listCalls(['status' => 'completed', 'limit' => 1]);
        if ($page->items === []) {
            self::markTestIncomplete('No completed calls in the default window.');
        }
        $id = $page->items[0]['id'];
        $detail = $ic->intelligence->getCall($id);
        self::assertSame($id, $detail['id']);
        self::assertArrayNotHasKey('vcon', $detail);
        $raw = $ic->calls->getCall($id)['data'];
        if (isset($raw['vcon'])) {
            self::assertSame([], array_values(array_diff(array_keys($raw['vcon']), ['recording_url', 'recording_url_expires_at', 'recording_mime_type'])));
        }
        self::assertArrayNotHasKey('overall_confidence', $detail);
        self::assertArrayNotHasKey('subject', $detail);

        $lines = $ic->intelligence->getTranscript($id);
        self::assertIsArray($lines);
        if ($lines !== []) {
            self::assertOnlyKeys($lines[0], ['time', 'speaker', 'name', 'text'], 'transcript line');
            self::assertMatchesRegularExpression('/^Speaker \d+$/', (string) $lines[0]['speaker']);
        }

        $rec = $ic->intelligence->getRecordingUrl($id);
        if ($rec !== null) {
            self::assertTrue(str_starts_with($rec->url, 'https://'), 'recording link is https');
            self::assertNotNull($rec->expiresAt);
        }
    }

    public function testAlertsAndGetAlert(): void
    {
        $ic = self::frontline();
        $page = $ic->intelligence->searchAlerts(dateFrom: new \DateTimeImmutable('-30 days'), limit: 5);
        self::assertArrayHasKey('total', $page->summary ?? []);
        if ($page->items === []) {
            self::markTestIncomplete('No alerts in 30 days.');
        }
        $a = $page->items[0];
        self::assertOnlyKeys($a, ['id', 'call_id', 'call_date', 'caller_timezone', 'caller_name', 'caller_number', 'callee_name', 'callee_number',
            'summary', 'sentiment', 'sentiment_score', 'sentiment_label', 'keywords', 'topics'], 'alert row');
        $one = $ic->intelligence->getAlert(strtoupper($a['id']));
        self::assertNotNull($one);
        self::assertSame($a['id'], $one['id']);

        $rule = $a['keywords'][0] ?? null;
        if (is_string($rule)) {
            $byRule = $ic->intelligence->searchAlerts(alertName: $rule, dateFrom: new \DateTimeImmutable('-30 days'), limit: 10);
            foreach ($byRule->items as $row) {
                self::assertContains($rule, $row['keywords']);
            }
        }
        $dash = $ic->intelligence->getAlertsDashboard(dateFrom: new \DateTimeImmutable('-7 days'));
        self::assertArrayHasKey('kpis', $dash);
    }

    public function testTasksAndInsights(): void
    {
        $ic = self::frontline();
        $tasks = $ic->intelligence->listTasks(status: 'open', dateFrom: new \DateTimeImmutable('-30 days'), limit: 5);
        self::assertIsArray($tasks->summary);
        self::assertArrayHasKey('total', $tasks->summary);

        $dash = $ic->intelligence->getInsightsDashboard(new \DateTimeImmutable('-7 days'), new \DateTimeImmutable('now'), 3);
        self::assertArrayHasKey('stats', $dash);
        $slug = $dash['topic_call_volumes'][0]['slug'] ?? null;
        if (is_string($slug)) {
            $calls = $ic->intelligence->listTopicCalls($slug, sentiment: 'negative', limit: 3);
            self::assertLessThanOrEqual(3, count($calls));
        }
    }

    public function testFiltersAreCheckedBeforeSending(): void
    {
        $this->expectException(FilterError::class);
        self::frontline()->calls->listCalls(['call_type' => 'inbound']);
    }
}
