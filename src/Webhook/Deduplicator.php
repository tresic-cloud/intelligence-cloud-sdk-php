<?php

declare(strict_types=1);

namespace IntelligenceCloud\Webhook;

/**
 * Drops repeated After Call deliveries. The same call can arrive more than once (retries, and
 * calls with several legs): de-duplicate on call.id.
 *
 *     $dedup = new Deduplicator();                    // in-memory LRU
 *     $dedup = new Deduplicator(new MySharedStore()); // a database or cache, shared between workers
 *     if (!$dedup->isDuplicate($event)) { queue($event->callId); }
 */
final class Deduplicator
{
    private readonly DedupStore $store;

    public function __construct(?DedupStore $store = null, int $capacity = 10_000)
    {
        $this->store = $store ?? new InMemoryDedupStore($capacity);
    }

    /**
     * True the first time a call id is seen; false for repeats.
     *
     * @deprecated use isDuplicate() (the same name and meaning in every Intelligence Cloud SDK).
     */
    public function isNew(AfterCallEvent|string $eventOrCallId): bool
    {
        $id = $eventOrCallId instanceof AfterCallEvent ? $eventOrCallId->callId : $eventOrCallId;
        return $this->store->add($id);
    }

    /** True when this call id was seen before; otherwise records it and returns false. */
    public function isDuplicate(AfterCallEvent|string $eventOrCallId): bool
    {
        return !$this->isNew($eventOrCallId);
    }
}
