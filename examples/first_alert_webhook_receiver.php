<?php
/**
 * A plain PHP endpoint for the First Alert webhook.
 *
 * Serve it over HTTPS on a long, unguessable path, e.g. https://hooks.example.com/ic/<token>/alerts,
 * and set it with $admin->intelligence->setFirstAlertWebhook($locationId, $url). The token comes
 * from the environment (at least 32 characters; make one with: php -r "echo bin2hex(random_bytes(32));").
 *
 * The request describes the alert that fired. This endpoint reads only the alert id, stores it
 * (a repeat delivery is dropped), answers 204, and then fetches the alert and its call with a
 * frontline integration user in Manager Alerts.
 *
 *   IC_ALERT_WEBHOOK_TOKEN=… IC_QUEUE_DSN=… IC_DATA_EMAIL=… IC_DATA_PASSWORD=… (in the PHP-FPM pool environment)
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use IntelligenceCloud\Client;
use IntelligenceCloud\Webhook\Deduplicator;
use IntelligenceCloud\Webhook\PdoDedupStore;

const MAX_BODY = 65536;

/** The alert id from a First Alert delivery, or null. Reads nothing else from the request. */
function alertIdFrom(string $body): ?string
{
    $data = json_decode($body, true);
    if (!is_array($data)) {
        return null;
    }
    $nested = is_array($data['alert'] ?? null) ? $data['alert'] : [];
    foreach ([$data['alert_id'] ?? null, $nested['id'] ?? null, $data['id'] ?? null] as $value) {
        if (is_string($value)) {
            $id = strtolower(trim($value));
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id) === 1) {
                return $id;
            }
        }
    }
    return null;
}

$token = (string) getenv('IC_ALERT_WEBHOOK_TOKEN');
if (strlen($token) < 32) {
    error_log('IC_ALERT_WEBHOOK_TOKEN must be at least 32 characters.');
    http_response_code(500);
    exit;
}

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals('/ic/' . $token . '/alerts', $path)) { // constant time
    http_response_code(404);
    exit;
}

$body = (string) file_get_contents('php://input', false, null, 0, MAX_BODY + 1);
if (strlen($body) > MAX_BODY) {
    http_response_code(413);
    exit;
}
$alertId = alertIdFrom($body);
if ($alertId === null) {
    http_response_code(204); // nothing this receiver handles: acknowledge and ignore
    exit;
}

// The shared store holds the alert ids: every worker sees them, and they survive a restart.
$store = new PdoDedupStore(new PDO((string) getenv('IC_QUEUE_DSN')), 'ic_first_alert_queue');
$store->createTable();
$repeat = (new Deduplicator($store))->isDuplicate($alertId);

http_response_code(204);
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request(); // answer now, work afterwards
}
if ($repeat) {
    exit;
}

$ic = new Client(email: (string) getenv('IC_DATA_EMAIL'), password: (string) getenv('IC_DATA_PASSWORD'));
$alert = $ic->intelligence->getAlert($alertId, withCall: true); // null when this login can't see it
if ($alert === null) {
    exit;
}
// Hand the alert id and a link behind your sign-in to your paging, chat or ticketing tool.
// Never log the summary, names or numbers.
