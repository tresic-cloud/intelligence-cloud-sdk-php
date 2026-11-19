<?php
/**
 * Once-a-day catch-up: webhook deliveries are dropped after three attempts, so re-read the last
 * 48 hours (calls are processed after they end) and handle any id you have not seen. It sees
 * only calls on the lines mapped to this integration user: map every line the location captures.
 *
 *   IC_DATA_EMAIL=… IC_DATA_PASSWORD=… php examples/nightly_catch_up.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use IntelligenceCloud\Client;

$ic = new Client(email: getenv('IC_DATA_EMAIL'), password: getenv('IC_DATA_PASSWORD'));

$to = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$from = $to->modify('-48 hours');

$processed = []; // load the call ids you already handled from your own store
$missed = 0;
foreach ($ic->intelligence->iterCalls(dateFrom: $from, dateTo: $to) as $call) {
    if (!isset($processed[$call['id']])) {
        $missed++;
        // process($call);
    }
}
echo "{$missed} calls caught up\n";
