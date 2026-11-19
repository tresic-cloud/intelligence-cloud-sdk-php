<?php

declare(strict_types=1);

namespace IntelligenceCloud\Webhook;

use IntelligenceCloud\Exception\PayloadTooLargeError;
use IntelligenceCloud\Exception\WebhookPayloadError;
use IntelligenceCloud\Internal\Projector;
use IntelligenceCloud\Internal\SpecTables;

/**
 * Receiver helpers for the After Call webhook.
 *
 * Deliveries are not signed: use a long, unguessable HTTPS URL and treat it as a secret.
 * Answer 2xx within 2 seconds and do the work afterwards. Redirects count as failures.
 * Treat an event as a notification: confirm it with getCall($event->callId) before you write
 * anything from it into another system. The same call can arrive more than once: upsert on call.id.
 */
final class Webhooks
{
    /** Largest body parseAfterCallEvent accepts by default (256 KB). Answer 413 above it. */
    public const MAX_EVENT_BYTES = 262144;

    /**
     * Parse and check an After Call delivery. Fields outside the published schema are dropped.
     *
     * Returns UnknownEvent for any other event type (answer 2xx and ignore it). Throws
     * PayloadTooLargeError for a body over $maxBytes (answer 413) and WebhookPayloadError for a
     * body that is not a JSON object or a malformed After Call event (answer 400).
     *
     * @param string|array<string,mixed> $body the raw request body, or the decoded JSON
     */
    public static function parseAfterCallEvent(string|array $body, int $maxBytes = self::MAX_EVENT_BYTES): AfterCallEvent|UnknownEvent
    {
        if (is_string($body)) {
            if (strlen($body) > $maxBytes) {
                throw new PayloadTooLargeError(sprintf('The body is larger than %d bytes.', $maxBytes));
            }
            try {
                $body = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new WebhookPayloadError('The body is not JSON.');
            }
        }
        if (!is_array($body) || array_is_list($body)) {
            throw new WebhookPayloadError('The body is not a JSON object.');
        }
        if (($body['type'] ?? null) !== 'end_of_call_summary') {
            $type = $body['type'] ?? null;
            $version = $body['version'] ?? null;
            return new UnknownEvent(is_string($type) ? $type : null, is_string($version) ? $version : null, $body);
        }
        $p = Projector::project($body, SpecTables::WEBHOOKS['afterCallSummary']);
        $call = $p['call'] ?? null;
        if (!is_array($call) || !isset($call['id']) || !is_string($call['id']) || $call['id'] === '') {
            throw new WebhookPayloadError('The event has no call.id.');
        }
        foreach (['location_id', 'timestamp'] as $k) {
            if (!isset($p[$k]) || !is_string($p[$k])) {
                throw new WebhookPayloadError("The event has no {$k}.");
            }
        }
        return new AfterCallEvent(
            version: (string) ($p['version'] ?? ''),
            type: 'end_of_call_summary',
            isTest: (bool) ($p['is_test'] ?? false),
            locationId: $p['location_id'],
            timestamp: $p['timestamp'],
            callId: $call['id'],
            call: $call,
            summary: isset($p['summary']) && is_array($p['summary']) ? $p['summary'] : null,
            location: isset($p['location']) && is_array($p['location']) ? $p['location'] : [],
            raw: $p,
        );
    }
}
