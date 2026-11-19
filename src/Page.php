<?php

declare(strict_types=1);

namespace IntelligenceCloud;

/**
 * One page of a list.
 *
 * - `$items`: the rows on this page (resource `attributes` already unwrapped).
 * - `$nextCursor` / `$hasMore`: pass `['cursor' => $page->nextCursor]` for the next page,
 *   or use the matching `iter*` method, which follows cursors for you.
 * - `$total`: the total across all pages, when the endpoint reports one.
 * - `$summary`: totals for the whole filter (calls, alerts, tasks), when present.
 * - `$raw`: the whole projected response body.
 *
 * @implements \IteratorAggregate<int, array<string,mixed>>
 */
final class Page implements \IteratorAggregate, \Countable
{
    /**
     * @param list<array<string,mixed>> $items
     * @param array<string,mixed>|null $summary
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public readonly array $items,
        public readonly bool $hasMore,
        public readonly ?string $nextCursor,
        public readonly ?int $total = null,
        public readonly ?array $summary = null,
        public readonly array $raw = [],
    ) {
    }

    /** @param array<string,mixed> $body projected response */
    public static function fromResponse(array $body): self
    {
        $data = $body['data'] ?? [];
        $summary = null;
        if (is_array($data) && array_is_list($data)) {
            $items = $data;
        } elseif (is_array($data) && isset($data['calls']) && is_array($data['calls'])) {
            $items = $data['calls'];
            $summary = $data['summary'] ?? null;
        } elseif (is_array($data) && isset($data['alerts']) && is_array($data['alerts'])) {
            $items = $data['alerts'];
            $summary = $data['summary'] ?? null;
        } else {
            $items = [];
        }
        if ($summary === null && isset($body['summary']) && is_array($body['summary'])) {
            $summary = $body['summary'];
        }
        $paging = $body['pagination'] ?? $body['meta'] ?? $body;
        $hasMore = (bool) ($paging['has_more'] ?? false);
        $next = $paging['next_cursor'] ?? null;
        $total = $paging['total_count'] ?? $paging['total'] ?? null;
        if ($total === null && is_array($summary)) {
            $total = $summary['total'] ?? $summary['total_calls'] ?? null;
        }
        $items = array_map(
            static fn ($i) => is_array($i) && isset($i['attributes']) && is_array($i['attributes']) ? $i['attributes'] : $i,
            array_values($items),
        );
        if (!$hasMore || !is_string($next) || $next === '') {
            $hasMore = false;
            $next = null;
        }
        return new self(
            $items,
            $hasMore,
            is_string($next) && $next !== '' ? $next : null,
            is_int($total) ? $total : null,
            is_array($summary) ? $summary : null,
            $body,
        );
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
