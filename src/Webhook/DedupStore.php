<?php

declare(strict_types=1);

namespace IntelligenceCloud\Webhook;

/**
 * Where the Deduplicator remembers ids. Implement it over a shared store (a database or cache) or your
 * framework's cache to de-duplicate across workers.
 */
interface DedupStore
{
    /**
     * Record $id. Return true if it was new, false if it was already there. Implementations
     * shared between processes should do this atomically (an insert-if-absent, e.g. INSERT … ON CONFLICT DO NOTHING).
     */
    public function add(string $id): bool;
}
