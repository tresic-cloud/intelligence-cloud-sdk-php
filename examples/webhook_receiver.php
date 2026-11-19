<?php
/**
 * A plain PHP endpoint for the After Call webhook.
 *
 * Serve it over HTTPS on a long, unguessable path, e.g. https://hooks.example.com/ic/<token>/after-call,
 * and set it with $ic->intelligence->setAfterCallWebhook($locationId, $url). The token comes from
 * the environment (at least 32 characters; make one with: php -r "echo bin2hex(random_bytes(32));").
 *
 *   IC_WEBHOOK_TOKEN=… IC_QUEUE_DSN=sqlite:/var/lib/ic/after-call.sqlite (in the PHP-FPM pool environment)
 *
 * Answer 2xx within 2 seconds and do the work afterwards. Deliveries are not signed and can
 * repeat. The call id is stored before the delivery is acknowledged; a repeat is dropped. Your
 * worker reads the stored ids and confirms each one with getCall() before writing anywhere.
 * Keep the token out of logs: redact this path in your web server, proxy and APM.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use IntelligenceCloud\Exception\PayloadTooLargeError;
use IntelligenceCloud\Exception\WebhookPayloadError;
use IntelligenceCloud\Webhook\Deduplicator;
use IntelligenceCloud\Webhook\PdoDedupStore;
use IntelligenceCloud\Webhook\UnknownEvent;
use IntelligenceCloud\Webhook\Webhooks;

$token = (string) getenv('IC_WEBHOOK_TOKEN');
if (strlen($token) < 32) {
    error_log('IC_WEBHOOK_TOKEN must be at least 32 characters.');
    http_response_code(500);
    exit;
}

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals('/ic/' . $token . '/after-call', $path)) { // constant time
    http_response_code(404);
    exit;
}

$body = (string) file_get_contents('php://input', false, null, 0, Webhooks::MAX_EVENT_BYTES + 1);
try {
    $event = Webhooks::parseAfterCallEvent($body);
} catch (PayloadTooLargeError) {
    http_response_code(413);
    exit;
} catch (WebhookPayloadError) {
    http_response_code(400);
    exit;
}

if ($event instanceof UnknownEvent || $event->isTest) {
    http_response_code(204); // an event type this receiver does not handle, or the test send
    exit;
}

// The shared store holds the call ids: every worker sees them, and they survive a restart.
$store = new PdoDedupStore(new PDO((string) getenv('IC_QUEUE_DSN')), 'ic_after_call_queue');
$store->createTable();
(new Deduplicator($store))->isDuplicate($event); // stores the id the first time; a repeat is dropped

http_response_code(204);
