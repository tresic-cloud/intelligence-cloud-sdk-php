<?php
/**
 * Read conversation data with frontline credentials.
 *
 *   IC_DATA_EMAIL=… IC_DATA_PASSWORD=… php examples/search_calls.php +15555550142
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use IntelligenceCloud\Client;

$ic = new Client(email: getenv('IC_DATA_EMAIL'), password: getenv('IC_DATA_PASSWORD'), userAgent: 'riverbend-bi-sync/1.0');
$intel = $ic->intelligence;
$intel->requireFrontline(); // admin logins get empty lists: fail fast

$page = $intel->searchCalls(phone: $argv[1] ?? '+15555550142', dateFrom: new DateTimeImmutable('-30 days'), limit: 10);
// Print counts and ids only: stdout ends up in cron mail and log collectors.
echo "{$page->total} calls\n";

foreach ($page as $call) {
    echo "call {$call['id']}\n";
}

if ($page->items !== []) {
    $id = $page->items[0]['id'];
    echo count($intel->getCall($id)['tasks'] ?? []) . " tasks on call {$id}\n";
    echo count($intel->getTranscript($id)) . " transcript lines on call {$id}\n";
    $rec = $intel->getRecordingUrl($id); // signed link, valid 120 minutes: never log it
    echo $rec === null ? "no recording\n" : "recording available\n";
}

$n = 0;
foreach ($intel->iterCalls(sentiment: 'negative', dateFrom: new DateTimeImmutable('monday this week')) as $call) {
    $n++;
}
echo "{$n} negative calls this week\n";
