<?php

declare(strict_types=1);

namespace IntelligenceCloud\Webhook;

/** Least-recently-used set of ids, in this process only. */
final class InMemoryDedupStore implements DedupStore
{
    /** @var array<string,true> */
    private array $ids = [];

    public function __construct(private readonly int $capacity = 10_000)
    {
        if ($capacity < 1) {
            throw new \InvalidArgumentException('capacity must be at least 1');
        }
    }

    public function add(string $id): bool
    {
        if (isset($this->ids[$id])) {
            unset($this->ids[$id]);
            $this->ids[$id] = true; // refresh recency
            return false;
        }
        $this->ids[$id] = true;
        if (count($this->ids) > $this->capacity) {
            unset($this->ids[array_key_first($this->ids)]);
        }
        return true;
    }
}
