<?php

declare(strict_types=1);

namespace IntelligenceCloud\Tests\Unit;

use IntelligenceCloud\Exception\FilterError;
use IntelligenceCloud\Tests\Unit\Support\TestCase;

final class FiltersAndPagingTest extends TestCase
{
    private const LOC = '0193b6c2-6b29-7a51-b0e4-1c8f3d5e7a02';

    /** @param callable(\IntelligenceCloud\Client):mixed $fn */
    private function refused(callable $fn, string $pattern): void
    {
        $ic = $this->scripted([]);
        try {
            $fn($ic);
            self::fail('expected FilterError');
        } catch (FilterError $e) {
            self::assertMatchesRegularExpression($pattern, $e->getMessage());
        }
        self::assertSame([], $this->history, 'nothing may be sent');
    }

    public function testUnknownFilterIsRefusedBeforeSending(): void
    {
        $this->refused(fn ($ic) => $ic->calls->listCalls(['direction' => 'inbound']), '/does not support the filter "direction"/');
        $this->refused(fn ($ic) => $ic->alerts->listAlerts(['rule_id' => 'x']), '/Supported: .*keyword/');
        $this->refused(fn ($ic) => $ic->users->listCompanyUsers('0193b6c2-5a11-7c3e-8f20-6d4b9e1c2a77', ['product' => 'insights-hub']), '/product/');
    }

    public function testDateOnlyIsRefused(): void
    {
        $this->refused(fn ($ic) => $ic->calls->listCalls(['date_from' => '2026-11-01']), '/full RFC 3339 timestamp/');
        $this->refused(fn ($ic) => $ic->intelligence->searchCalls(dateFrom: '2026-11-01T00:00:00'), '/full RFC 3339/');
    }

    public function testDatesAreSentAsUtc(): void
    {
        $ic = $this->scripted([self::login(), self::json(200, ['data' => ['summary' => [], 'calls' => []], 'meta' => ['has_more' => false]])]);
        $ic->calls->listCalls([
            'date_from' => new \DateTimeImmutable('2026-11-01 00:00:00', new \DateTimeZone('America/Chicago')),
            'date_to' => '2026-11-08T00:00:00-06:00',
        ]);
        parse_str($this->request(1)->getUri()->getQuery(), $q);
        self::assertSame('2026-11-01T05:00:00Z', $q['date_from']);
        self::assertSame('2026-11-08T06:00:00Z', $q['date_to']);
    }

    public function testNumbersAreDigitsOnlyAndAtMost25(): void
    {
        $ic = $this->scripted([self::login(), self::me(), self::json(200, ['data' => ['summary' => ['total' => 0], 'alerts' => []], 'meta' => []])]);
        $ic->intelligence->searchAlerts(numbers: ['+1 (555) 555-0142', '2101', '2101']);
        parse_str($this->request(2)->getUri()->getQuery(), $q);
        self::assertSame('5555550142,2101', $q['numbers']); // NANP: last 10 digits

        $many = array_map(fn ($i) => (string) (2000 + $i), range(1, 26));
        $this->refused(fn ($ic) => $ic->alerts->listAlerts(['numbers' => $many]), '/at most 25/');
    }

    public function testLocationIdsAtMost50(): void
    {
        $ids = array_map(fn ($i) => sprintf('00000000-0000-4000-8000-%012d', $i), range(1, 51));
        $this->refused(fn ($ic) => $ic->calls->listCalls(['location_id' => $ids]), '/at most 50/');
        $this->refused(fn ($ic) => $ic->calls->listCalls(['location_id' => ['not-a-uuid']]), '/UUIDs/');
    }

    public function testValueChecks(): void
    {
        $this->refused(fn ($ic) => $ic->calls->listCalls(['limit' => 101]), '/between 1 and 100/');
        $this->refused(fn ($ic) => $ic->calls->listCalls(['status' => 'COMPLETED']), '/one of: completed/');
        $this->refused(fn ($ic) => $ic->calls->listCalls(['sentiment' => 'angry']), '/crisis/');
        $this->refused(fn ($ic) => $ic->calls->listCalls(['has_open_tasks' => false]), '/only accepts true/');
        $this->refused(fn ($ic) => $ic->calls->listCalls(['search' => 'a']), '/at least 2 characters/');
        $this->refused(fn ($ic) => $ic->insights->listTopicCalls('billing', ['sentiment' => 'very_negative']), '/positive, neutral, negative/');
        $this->refused(fn ($ic) => $ic->insights->getInsightsDashboard(['start' => '2026-11-01T00:00:00Z']), '/needs the "end" filter/');
        $this->refused(fn ($ic) => $ic->tasks->listTasks(['due_on' => '2026-11-01T00:00:00Z']), '/YYYY-MM-DD/');
        $this->refused(fn ($ic) => $ic->intelligence->searchCalls(phone: '2101', text: 'refund'), '/not both/');
        $this->refused(fn ($ic) => $ic->intelligence->searchCalls(text: 'a'), '/at least 2 characters/');
        $this->refused(fn ($ic) => $ic->calls->iterCalls(['bogus' => 1]), '/bogus/');
    }

    public function testUnknownBodyFieldIsRefused(): void
    {
        // mfa_enforced is silently ignored on create by the API: refuse it here.
        $this->refused(fn ($ic) => $ic->companies->createCompany('00000000-0000-4000-8000-0000000000aa', ['company_name' => 'X', 'mfa_enforced' => false]), '/mfa_enforced/');
        $this->refused(fn ($ic) => $ic->firstAlert->saveFirstAlertConfig(self::LOC, ['connector_mappings' => []]), '/send webhook_url every time/');
    }

    public function testBooleansAndListsAreEncoded(): void
    {
        $ic = $this->scripted([self::login(), self::json(200, ['data' => ['summary' => [], 'calls' => []], 'meta' => []])]);
        $ic->calls->listCalls(['multi_leg' => true, 'has_open_tasks' => true, 'location_id' => [self::LOC, strtoupper(self::LOC)]]);
        parse_str($this->request(1)->getUri()->getQuery(), $q);
        self::assertSame(['multi_leg' => 'true', 'has_open_tasks' => 'true', 'location_id' => self::LOC], $q);
    }

    public function testTodayStartKeepsTheLocalOffset(): void
    {
        $ic = $this->scripted([self::login(), self::json(200, ['data' => [], 'meta' => []])]);
        $ic->tasks->listTasks(['today_start' => '2026-11-02T00:00:00-06:00', 'status' => 'overdue']);
        parse_str($this->request(1)->getUri()->getQuery(), $q);
        self::assertSame('2026-11-02T00:00:00-06:00', $q['today_start']);
    }

    // ------------------------------------------------------------ paging

    public function testIteratorFollowsCursorsWithLimit100(): void
    {
        $cursor = 'eyJkIjoiMjAyNi0xMS0wMlQxNjo0MTowN1oifQ+/=';
        $ic = $this->scripted([
            self::login(),
            self::json(200, ['data' => ['summary' => ['total_calls' => 3], 'calls' => [['id' => 'a'], ['id' => 'b']]], 'meta' => ['has_more' => true, 'next_cursor' => $cursor, 'limit' => 100]]),
            self::json(200, ['data' => ['summary' => ['total_calls' => 3], 'calls' => [['id' => 'c']]], 'meta' => ['has_more' => false, 'next_cursor' => null, 'limit' => 100]]),
        ]);
        $ids = array_column(iterator_to_array($ic->calls->iterCalls(['status' => 'completed']), false), 'id');
        self::assertSame(['a', 'b', 'c'], $ids);
        self::assertSame('limit=100&status=completed', $this->sortedQuery(1));
        parse_str($this->request(2)->getUri()->getQuery(), $q);
        self::assertSame($cursor, $q['cursor']);
        self::assertStringContainsString('cursor=eyJkIjoiMjAyNi0xMS0wMlQxNjo0MTowN1oifQ%2B%2F%3D', $this->request(2)->getUri()->getQuery());
    }

    public function testCompanyListPagingIsTopLevel(): void
    {
        $p1 = fn () => self::json(200, ['data' => [['type' => 'company', 'attributes' => ['id' => '1']]], 'has_more' => true, 'next_cursor' => 'c2', 'total_count' => 2]);
        $ic = $this->scripted([
            self::login(),
            $p1(),
            $p1(),
            self::json(200, ['data' => [['type' => 'company', 'attributes' => ['id' => '2']]], 'has_more' => false, 'total_count' => 2]),
        ]);
        $page = $ic->companies->listCompanies('00000000-0000-4000-8000-0000000000aa');
        self::assertTrue($page->hasMore);
        self::assertSame('c2', $page->nextCursor);
        self::assertSame(2, $page->total);
        self::assertCount(1, $page);
        $rows = iterator_to_array($ic->companies->iterCompanies('00000000-0000-4000-8000-0000000000aa'), false);
        self::assertSame(['1', '2'], array_column($rows, 'id'), 'items are unwrapped from type/attributes');
    }

    public function testIteratorStopsOnARepeatedCursor(): void
    {
        $p = fn () => self::json(200, ['data' => [['type' => 'user_group', 'attributes' => ['id' => 'g']]], 'pagination' => ['has_more' => true, 'next_cursor' => 'same']]);
        $ic = $this->scripted([self::login(), $p(), $p(), $p()]);
        self::assertCount(2, iterator_to_array($ic->groups->iterUserGroups(self::LOC), false));
    }

    public function testPageSummaryAndTotals(): void
    {
        $ic = $this->scripted([self::login(), self::json(200, ['data' => ['summary' => ['total' => 7], 'alerts' => [['id' => 'x', 'keywords' => ['Upset Customer']]]], 'meta' => ['has_more' => false]])]);
        $page = $ic->alerts->listAlerts(['keyword' => 'Upset Customer']);
        self::assertSame(7, $page->total);
        self::assertSame(['total' => 7], $page->summary);
    }

    private function sortedQuery(int $i): string
    {
        parse_str($this->request($i)->getUri()->getQuery(), $q);
        ksort($q);
        return http_build_query($q);
    }
}
