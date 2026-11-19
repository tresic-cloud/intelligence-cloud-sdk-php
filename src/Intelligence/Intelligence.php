<?php

declare(strict_types=1);

namespace IntelligenceCloud\Intelligence;

use IntelligenceCloud\Api\Mappings;
use IntelligenceCloud\Client;
use IntelligenceCloud\Exception\FilterError;
use IntelligenceCloud\Exception\FrontlineRequiredError;
use IntelligenceCloud\Internal\Query;
use IntelligenceCloud\Internal\SpecTables;
use IntelligenceCloud\Page;

/**
 * Conversation intelligence: read calls, alerts, tasks and insights with a **frontline** login,
 * and configure the webhooks with an admin login.
 *
 * Admin logins get empty lists from the conversation-data endpoints. The read methods check the
 * role once and throw FrontlineRequiredError instead of returning empty data.
 * Reads return the record (the response's `data`); lists return a Page.
 */
final class Intelligence
{
    /** @var array<string,mixed>|null */
    private ?array $me = null;

    public function __construct(private readonly Client $c)
    {
    }

    // ================================================================ account

    /** The signed-in user (GET /me): role, company, locations, products. @return array<string,mixed> */
    public function whoami(): array
    {
        $this->me = Client::unwrap($this->c->account->getMe()) ?? [];
        return $this->me;
    }

    /** Throw FrontlineRequiredError unless this is a frontline login. @return array<string,mixed> */
    public function requireFrontline(): array
    {
        $me = $this->me ?? $this->whoami();
        $role = $me['role'] ?? null;
        if ($role !== 'frontline_user') {
            throw new FrontlineRequiredError(is_string($role) ? $role : null);
        }
        return $me;
    }

    // ================================================================ calls

    /**
     * One page of calls, newest first. `$phone` and `$text` both map to the API's one search
     * field: pass one or the other. Phone numbers match in any format, partial digits too.
     * Without `$dateFrom` the window starts at 00:00 UTC seven days ago.
     *
     * @param list<string>|string|null $locationIds up to 50
     */
    public function searchCalls(
        ?string $phone = null,
        ?string $text = null,
        \DateTimeInterface|string|null $dateFrom = null,
        \DateTimeInterface|string|null $dateTo = null,
        ?string $status = null,
        ?string $sentiment = null,
        ?bool $hasOpenTasks = null,
        ?bool $multiLeg = null,
        array|string|null $locationIds = null,
        ?string $companyId = null,
        ?int $limit = null,
        ?string $cursor = null,
    ): Page {
        $f = $this->callFilters($phone, $text, $dateFrom, $dateTo, $status, $sentiment, $hasOpenTasks, $multiLeg, $locationIds, $companyId);
        return $this->c->calls->listCalls($this->checked('listCalls', $f + self::paging($limit, $cursor)));
    }

    /**
     * Every matching call, following cursors 100 at a time.
     *
     * @param list<string>|string|null $locationIds
     * @return \Generator<int, array<string,mixed>>
     */
    public function iterCalls(
        ?string $phone = null,
        ?string $text = null,
        \DateTimeInterface|string|null $dateFrom = null,
        \DateTimeInterface|string|null $dateTo = null,
        ?string $status = null,
        ?string $sentiment = null,
        ?bool $hasOpenTasks = null,
        ?bool $multiLeg = null,
        array|string|null $locationIds = null,
        ?string $companyId = null,
    ): \Generator {
        $f = $this->callFilters($phone, $text, $dateFrom, $dateTo, $status, $sentiment, $hasOpenTasks, $multiLeg, $locationIds, $companyId);
        return $this->c->calls->iterCalls($this->checked('listCalls', $f));
    }

    /** Call detail: summary, analysis, sentiment, tasks. @return array<string,mixed> */
    public function getCall(string $callId): array
    {
        $this->requireFrontline();
        $call = Client::unwrap($this->c->calls->getCall($callId)) ?? [];
        unset($call['vcon']); // the recording link comes only from getRecordingUrl()
        return $call;
    }

    /** Transcript lines: time, speaker ("Speaker 1" …), name, text. @return list<array<string,mixed>> */
    public function getTranscript(string $callId): array
    {
        $this->requireFrontline();
        return (Client::unwrap($this->c->calls->getCallTranscript($callId)) ?? [])['transcript'] ?? [];
    }

    /**
     * The call's recording link and its expiry, or null when the call has no recording. Only the
     * link and expiry are returned. Ask for a fresh link when it expires (120 minutes).
     */
    public function getRecordingUrl(string $callId): ?RecordingLink
    {
        $this->requireFrontline();
        $rec = (Client::unwrap($this->c->calls->getCall($callId)) ?? [])['vcon'] ?? null;
        $url = is_array($rec) ? ($rec['recording_url'] ?? null) : null;
        if (!is_string($url) || $url === '') {
            return null;
        }
        $exp = $rec['recording_url_expires_at'] ?? null;
        $when = null;
        if (is_string($exp) && $exp !== '') {
            try {
                $when = new \DateTimeImmutable($exp);
            } catch (\Exception) {
                $when = null;
            }
        }
        return new RecordingLink($url, $when);
    }

    // ================================================================ alerts

    /**
     * One page of First Alert hits, newest first. `$alertName` is the exact, case-sensitive rule
     * name. `$numbers` (up to 25) match caller or callee by substring; formatting is stripped and a North American number is sent as its last 10 digits.
     *
     * @param list<string>|string|null $numbers
     * @param list<string>|string|null $locationIds
     */
    public function searchAlerts(
        ?string $alertName = null,
        array|string|null $numbers = null,
        ?string $text = null,
        ?string $sentiment = null,
        \DateTimeInterface|string|null $dateFrom = null,
        \DateTimeInterface|string|null $dateTo = null,
        array|string|null $locationIds = null,
        ?string $topic = null,
        ?string $companyId = null,
        ?int $limit = null,
        ?string $cursor = null,
    ): Page {
        $f = $this->alertFilters($alertName, $numbers, $text, $sentiment, $dateFrom, $dateTo, $locationIds, $topic, $companyId);
        return $this->c->alerts->listAlerts($this->checked('listAlerts', $f + self::paging($limit, $cursor)));
    }

    /**
     * @param list<string>|string|null $numbers
     * @param list<string>|string|null $locationIds
     * @return \Generator<int, array<string,mixed>>
     */
    public function iterAlerts(
        ?string $alertName = null,
        array|string|null $numbers = null,
        ?string $text = null,
        ?string $sentiment = null,
        \DateTimeInterface|string|null $dateFrom = null,
        \DateTimeInterface|string|null $dateTo = null,
        array|string|null $locationIds = null,
        ?string $topic = null,
        ?string $companyId = null,
    ): \Generator {
        $f = $this->alertFilters($alertName, $numbers, $text, $sentiment, $dateFrom, $dateTo, $locationIds, $topic, $companyId);
        return $this->c->alerts->iterAlerts($this->checked('listAlerts', $f));
    }

    /**
     * One alert by id (searched by the lowercase id, with no date window). With $withCall the call
     * detail is added under 'call'. Null when the alert is not visible.
     *
     * @return array<string,mixed>|null
     */
    public function getAlert(string $alertId, bool $withCall = false): ?array
    {
        $id = strtolower(trim($alertId));
        if (!Query::isUuid($id)) {
            throw new FilterError('getAlert needs an alert id (UUID).');
        }
        $this->requireFrontline();
        foreach ($this->c->alerts->listAlerts(['search' => $id, 'limit' => 10])->items as $a) {
            if (($a['id'] ?? null) === $id) {
                if ($withCall && isset($a['call_id'])) {
                    $a['call'] = $this->getCall((string) $a['call_id']);
                }
                return $a;
            }
        }
        return null;
    }

    /**
     * @param list<string>|string|null $locationIds
     * @return array<string,mixed>
     */
    public function getAlertsDashboard(
        \DateTimeInterface|string|null $dateFrom = null,
        \DateTimeInterface|string|null $dateTo = null,
        array|string|null $locationIds = null,
        ?string $companyId = null,
    ): array {
        $this->requireFrontline();
        $f = self::compact(['date_from' => $dateFrom, 'date_to' => $dateTo, 'location_id' => $locationIds, 'company_id' => $companyId]);
        return Client::unwrap($this->c->alerts->getAlertsDashboard($f)) ?? [];
    }

    // ================================================================ tasks

    /**
     * One page of follow-up tasks. `$dateFrom`/`$dateTo` filter on creation time; there is no
     * default window. `$todayStart` is your local midnight, e.g. 2026-11-02T00:00:00-06:00.
     *
     * @param list<string>|string|null $locationIds
     */
    public function listTasks(
        ?string $status = null,
        ?string $callId = null,
        ?string $taskId = null,
        \DateTimeInterface|string|null $dueOn = null,
        \DateTimeInterface|string|null $dateFrom = null,
        \DateTimeInterface|string|null $dateTo = null,
        ?string $search = null,
        array|string|null $locationIds = null,
        \DateTimeInterface|string|null $todayStart = null,
        ?string $companyId = null,
        ?int $limit = null,
        ?string $cursor = null,
    ): Page {
        $f = $this->taskFilters($status, $callId, $taskId, $dueOn, $dateFrom, $dateTo, $search, $locationIds, $todayStart, $companyId);
        return $this->c->tasks->listTasks($this->checked('listTasks', $f + self::paging($limit, $cursor)));
    }

    /**
     * @param list<string>|string|null $locationIds
     * @return \Generator<int, array<string,mixed>>
     */
    public function iterTasks(
        ?string $status = null,
        ?string $callId = null,
        ?string $taskId = null,
        \DateTimeInterface|string|null $dueOn = null,
        \DateTimeInterface|string|null $dateFrom = null,
        \DateTimeInterface|string|null $dateTo = null,
        ?string $search = null,
        array|string|null $locationIds = null,
        \DateTimeInterface|string|null $todayStart = null,
        ?string $companyId = null,
    ): \Generator {
        $f = $this->taskFilters($status, $callId, $taskId, $dueOn, $dateFrom, $dateTo, $search, $locationIds, $todayStart, $companyId);
        return $this->c->tasks->iterTasks($this->checked('listTasks', $f));
    }

    // ================================================================ insights

    /**
     * Insights Hub dashboard for a window (both ends required, at most 366 days apart).
     *
     * @param list<string>|string|null $locationIds
     * @return array<string,mixed>
     */
    public function getInsightsDashboard(
        \DateTimeInterface|string $start,
        \DateTimeInterface|string $end,
        ?int $trendingLimit = null,
        array|string|null $locationIds = null,
        ?string $companyId = null,
    ): array {
        $this->requireFrontline();
        $f = self::compact(['start' => $start, 'end' => $end, 'trending_limit' => $trendingLimit, 'location_id' => $locationIds, 'company_id' => $companyId]);
        return Client::unwrap($this->c->insights->getInsightsDashboard($f)) ?? [];
    }

    /**
     * Calls for one topic (slug from the dashboard's `topic_call_volumes`).
     *
     * @param list<string>|string|null $locationIds
     */
    public function listTopicCalls(
        string $slug,
        \DateTimeInterface|string|null $dateFrom = null,
        \DateTimeInterface|string|null $dateTo = null,
        ?string $sentiment = null,
        ?string $outcome = null,
        ?string $search = null,
        array|string|null $locationIds = null,
        ?string $companyId = null,
        ?int $limit = null,
        ?string $cursor = null,
    ): Page {
        $this->requireFrontline();
        $f = self::compact(['date_from' => $dateFrom, 'date_to' => $dateTo, 'sentiment' => $sentiment, 'outcome' => $outcome,
            'search' => self::searchTerm(null, $search), 'location_id' => $locationIds, 'company_id' => $companyId]);
        return $this->c->insights->listTopicCalls($slug, $f + self::paging($limit, $cursor));
    }

    /**
     * @param list<string>|string|null $locationIds
     * @return \Generator<int, array<string,mixed>>
     */
    public function iterTopicCalls(
        string $slug,
        \DateTimeInterface|string|null $dateFrom = null,
        \DateTimeInterface|string|null $dateTo = null,
        ?string $sentiment = null,
        ?string $outcome = null,
        ?string $search = null,
        array|string|null $locationIds = null,
        ?string $companyId = null,
    ): \Generator {
        $this->requireFrontline();
        $f = self::compact(['date_from' => $dateFrom, 'date_to' => $dateTo, 'sentiment' => $sentiment, 'outcome' => $outcome,
            'search' => self::searchTerm(null, $search), 'location_id' => $locationIds, 'company_id' => $companyId]);
        return $this->c->insights->iterTopicCalls($slug, $f);
    }

    // ================================================================ webhook configuration (admin)

    /** The location's After Call webhook (webhook_url, enabled), or null when not set. @return array<string,mixed>|null */
    public function getAfterCallWebhook(string $locationId): ?array
    {
        return Client::unwrap($this->c->webhooks->getAfterCallWebhook($locationId));
    }

    /**
     * Set the After Call webhook (HTTPS). It receives every call at the location. Pause with
     * $enabled = false; there is no delete. Treat the URL as a secret.
     *
     * @return array<string,mixed>|null
     */
    public function setAfterCallWebhook(string $locationId, string $url, bool $enabled = true): ?array
    {
        self::https($url);
        return Client::unwrap($this->c->webhooks->saveAfterCallWebhook($locationId, ['webhook_url' => $url, 'enabled' => $enabled]));
    }

    /** Send the test payload to the saved URL. */
    public function testAfterCallWebhook(string $locationId): WebhookTestResult
    {
        $d = Client::unwrap($this->c->webhooks->testAfterCallWebhook($locationId)) ?? [];
        return new WebhookTestResult((bool) ($d['success'] ?? false), isset($d['http_status']) ? (int) $d['http_status'] : null);
    }

    /** The First Alert location webhook URL, or null. */
    public function getFirstAlertWebhook(string $locationId): ?string
    {
        $url = (Client::unwrap($this->c->firstAlert->getFirstAlertConfig($locationId)) ?? [])['webhook_url'] ?? null;
        return is_string($url) && $url !== '' ? $url : null;
    }

    /**
     * Set (or clear with null) the First Alert location webhook. Monitored numbers are kept.
     *
     * @return array<string,mixed>|null
     */
    public function setFirstAlertWebhook(string $locationId, ?string $url): ?array
    {
        if ($url !== null) {
            self::https($url);
        }
        $cfg = Client::unwrap($this->c->firstAlert->getFirstAlertConfig($locationId)) ?? [];
        $mappings = [];
        foreach ($cfg['connector_mappings'] ?? [] as $m) {
            $mappings = Mappings::edit($mappings, (string) $m['connector_id'], $m['monitored_numbers'] ?? []);
        }
        return Client::unwrap($this->c->firstAlert->saveFirstAlertConfig($locationId, ['webhook_url' => $url, 'connector_mappings' => $mappings]));
    }

    /** Send a test to $url (default: the saved URL). */
    public function testFirstAlertWebhook(string $locationId, ?string $url = null): WebhookTestResult
    {
        $target = $url ?? $this->getFirstAlertWebhook($locationId);
        if ($target === null || $target === '') {
            throw new FilterError('No First Alert webhook URL is set for this location.');
        }
        self::https($target);
        $d = Client::unwrap($this->c->firstAlert->testFirstAlertWebhook($locationId, ['url' => $target])) ?? [];
        return new WebhookTestResult((bool) ($d['success'] ?? false), isset($d['status_code']) ? (int) $d['status_code'] : null);
    }

    // ================================================================ internals

    /** @return array<string,mixed> */
    private function callFilters(?string $phone, ?string $text, mixed $dateFrom, mixed $dateTo, ?string $status, ?string $sentiment,
        ?bool $hasOpenTasks, ?bool $multiLeg, array|string|null $locationIds, ?string $companyId): array
    {
        if ($hasOpenTasks === false) {
            throw new FilterError('has_open_tasks only filters for true. Leave it out otherwise.');
        }
        return self::compact([
            'search' => self::searchTerm($phone, $text), 'date_from' => $dateFrom, 'date_to' => $dateTo, 'status' => $status,
            'sentiment' => $sentiment, 'has_open_tasks' => $hasOpenTasks, 'multi_leg' => $multiLeg,
            'location_id' => $locationIds, 'company_id' => $companyId,
        ]);
    }

    /** @return array<string,mixed> */
    private function alertFilters(?string $alertName, array|string|null $numbers, ?string $text, ?string $sentiment, mixed $dateFrom,
        mixed $dateTo, array|string|null $locationIds, ?string $topic, ?string $companyId): array
    {
        if (is_string($numbers)) {
            $numbers = [$numbers];
        }
        return self::compact([
            'keyword' => $alertName, 'numbers' => $numbers, 'search' => self::searchTerm(null, $text), 'sentiment' => $sentiment,
            'date_from' => $dateFrom, 'date_to' => $dateTo, 'location_id' => $locationIds, 'topic' => $topic, 'company_id' => $companyId,
        ]);
    }

    /** @return array<string,mixed> */
    private function taskFilters(?string $status, ?string $callId, ?string $taskId, mixed $dueOn, mixed $dateFrom, mixed $dateTo,
        ?string $search, array|string|null $locationIds, mixed $todayStart, ?string $companyId): array
    {
        if ($taskId !== null && $dueOn !== null) {
            throw new FilterError('listTasks: task_id cannot be combined with due_on.');
        }
        return self::compact([
            'status' => $status, 'call_id' => $callId, 'task_id' => $taskId, 'due_on' => $dueOn, 'date_from' => $dateFrom,
            'date_to' => $dateTo, 'search' => self::searchTerm(null, $search), 'location_id' => $locationIds,
            'today_start' => $todayStart, 'company_id' => $companyId,
        ]);
    }

    /**
     * Check filters against the spec (nothing is sent for a bad filter), then the role.
     *
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function checked(string $operationId, array $filters): array
    {
        Query::build($operationId, SpecTables::OPERATIONS[$operationId]['query'], $filters);
        $this->requireFrontline();
        return $filters;
    }

    private static function searchTerm(?string $phone, ?string $text): ?string
    {
        if ($phone !== null && $text !== null) {
            throw new FilterError('Pass phone or text, not both: the API has one search field.');
        }
        $term = $phone ?? $text;
        if ($term === null) {
            return null;
        }
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            throw new FilterError('Search terms need at least 2 characters (shorter ones are ignored by the API).');
        }
        return $term;
    }

    /** @return array<string,mixed> */
    private static function paging(?int $limit, ?string $cursor): array
    {
        return self::compact(['limit' => $limit, 'cursor' => $cursor]);
    }

    /** @param array<string,mixed> $a @return array<string,mixed> */
    private static function compact(array $a): array
    {
        return array_filter($a, static fn ($v) => $v !== null);
    }

    private static function https(string $url): void
    {
        if (stripos($url, 'https://') !== 0) {
            throw new FilterError('Webhook URLs must use HTTPS.');
        }
    }
}
