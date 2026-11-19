<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Alerts operations, generated from openapi.yaml. Do not edit by hand. */
class AlertsApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** List alerts. `GET /alerts` */
    public function listAlerts(array $filters = []): Page
    {
        return $this->c->page('listAlerts', [], $filters);
    }

    /** Every row of listAlerts(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterAlerts(array $filters = []): \Generator
    {
        return $this->c->iterate('listAlerts', [], $filters);
    }

    /** Alert totals. `GET /alerts/dashboard` @return array<string,mixed>|null */
    public function getAlertsDashboard(array $filters = []): ?array
    {
        return $this->c->call('getAlertsDashboard', [], $filters, null);
    }
}
