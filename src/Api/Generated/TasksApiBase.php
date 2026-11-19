<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Tasks operations, generated from openapi.yaml. Do not edit by hand. */
class TasksApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** List tasks. `GET /tasks` */
    public function listTasks(array $filters = []): Page
    {
        return $this->c->page('listTasks', [], $filters);
    }

    /** Every row of listTasks(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterTasks(array $filters = []): \Generator
    {
        return $this->c->iterate('listTasks', [], $filters);
    }
}
