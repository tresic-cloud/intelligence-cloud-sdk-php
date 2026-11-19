<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Webhooks operations, generated from openapi.yaml. Do not edit by hand. */
class WebhooksApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** Read the After Call webhook. `GET /locations/{locationId}/after-call/automation` @return array<string,mixed>|null */
    public function getAfterCallWebhook(string $locationId): ?array
    {
        return $this->c->call('getAfterCallWebhook', ['locationId' => $locationId], [], null);
    }

    /** Set the After Call webhook. `PUT /locations/{locationId}/after-call/automation` @return array<string,mixed>|null */
    public function saveAfterCallWebhook(string $locationId, array $body): ?array
    {
        return $this->c->call('saveAfterCallWebhook', ['locationId' => $locationId], [], $body);
    }

    /** Send a test After Call event. `POST /locations/{locationId}/after-call/automation/test` @return array<string,mixed>|null */
    public function testAfterCallWebhook(string $locationId): ?array
    {
        return $this->c->call('testAfterCallWebhook', ['locationId' => $locationId], [], null);
    }
}
