<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Insights operations, generated from openapi.yaml. Do not edit by hand. */
class InsightsApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** Insights Hub dashboard. `GET /insights/dashboard` @return array<string,mixed>|null */
    public function getInsightsDashboard(array $filters = []): ?array
    {
        return $this->c->call('getInsightsDashboard', [], $filters, null);
    }

    /** Calls for one topic. `GET /insights/topics/{slug}/calls` */
    public function listTopicCalls(string $slug, array $filters = []): Page
    {
        return $this->c->page('listTopicCalls', ['slug' => $slug], $filters);
    }

    /** Every row of listTopicCalls(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterTopicCalls(string $slug, array $filters = []): \Generator
    {
        return $this->c->iterate('listTopicCalls', ['slug' => $slug], $filters);
    }
}
