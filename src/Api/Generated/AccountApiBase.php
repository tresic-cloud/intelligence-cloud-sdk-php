<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Account operations, generated from openapi.yaml. Do not edit by hand. */
class AccountApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** Who is signed in. `GET /me` @return array<string,mixed>|null */
    public function getMe(): ?array
    {
        return $this->c->call('getMe', [], [], null);
    }

    /** Locations this login can see. `GET /locations` */
    public function listVisibleLocations(array $filters = []): Page
    {
        return $this->c->page('listVisibleLocations', [], $filters);
    }

    /** Every row of listVisibleLocations(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterVisibleLocations(array $filters = []): \Generator
    {
        return $this->c->iterate('listVisibleLocations', [], $filters);
    }
}
