<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Calls operations, generated from openapi.yaml. Do not edit by hand. */
class CallsApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** List calls. `GET /calls` */
    public function listCalls(array $filters = []): Page
    {
        return $this->c->page('listCalls', [], $filters);
    }

    /** Every row of listCalls(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterCalls(array $filters = []): \Generator
    {
        return $this->c->iterate('listCalls', [], $filters);
    }

    /** Read a call. `GET /calls/{callId}` @return array<string,mixed>|null */
    public function getCall(string $callId): ?array
    {
        return $this->c->call('getCall', ['callId' => $callId], [], null);
    }

    /** Read a call's transcript. `GET /calls/{callId}/transcript` @return array<string,mixed>|null */
    public function getCallTranscript(string $callId): ?array
    {
        return $this->c->call('getCallTranscript', ['callId' => $callId], [], null);
    }
}
