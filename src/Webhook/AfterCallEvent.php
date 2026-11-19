<?php

declare(strict_types=1);

namespace IntelligenceCloud\Webhook;

/**
 * An After Call webhook delivery (`type: end_of_call_summary`), projected to the published
 * schema. `$callId` is the same id as GET /calls/{callId}: de-duplicate on it, and fetch full
 * detail with getCall($event->callId).
 */
final class AfterCallEvent
{
    /**
     * @param array<string,mixed> $call
     * @param array<string,mixed>|null $summary
     * @param array<string,mixed> $location
     * @param array<string,mixed> $raw the whole projected payload
     */
    public function __construct(
        public readonly string $version,
        public readonly string $type,
        public readonly bool $isTest,
        public readonly string $locationId,
        public readonly string $timestamp,
        public readonly string $callId,
        public readonly array $call,
        public readonly ?array $summary,
        public readonly array $location,
        public readonly array $raw,
    ) {
    }

    public function callType(): ?string
    {
        return isset($this->call['call_type']) ? (string) $this->call['call_type'] : null;
    }

    public function callerNumber(): ?string
    {
        return isset($this->call['caller_number']) ? (string) $this->call['caller_number'] : null;
    }

    public function calleeNumber(): ?string
    {
        return isset($this->call['callee_number']) ? (string) $this->call['callee_number'] : null;
    }

    public function durationSeconds(): ?int
    {
        return isset($this->call['duration_seconds']) ? (int) $this->call['duration_seconds'] : null;
    }

    public function summaryText(): ?string
    {
        return isset($this->summary['text']) ? (string) $this->summary['text'] : null;
    }

    public function sentiment(): ?string
    {
        return isset($this->summary['sentiment']) ? (string) $this->summary['sentiment'] : null;
    }
}
