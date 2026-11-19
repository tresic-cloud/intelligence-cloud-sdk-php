<?php

// app/Http/Controllers/AfterCallWebhookController.php
// Route (routes/api.php):
//   Route::post('/ic/{token}/after-call', AfterCallWebhookController::class);
// Exclude the route from CSRF protection if you register it in routes/web.php.
// config/services.php: 'intelligence_cloud' => ['webhook_token' => env('IC_WEBHOOK_TOKEN')],
// Answer within 2 seconds; keep the token out of access logs.

namespace App\Http\Controllers;

use App\Jobs\ProcessAfterCallEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use IntelligenceCloud\Exception\PayloadTooLargeError;
use IntelligenceCloud\Exception\WebhookPayloadError;
use IntelligenceCloud\Webhook\UnknownEvent;
use IntelligenceCloud\Webhook\Webhooks;

class AfterCallWebhookController
{
    public function __invoke(Request $request, string $token): Response
    {
        // The token comes from the environment (IC_WEBHOOK_TOKEN, at least 32 characters).
        $expected = (string) config('services.intelligence_cloud.webhook_token');
        if (strlen($expected) < 32) {
            abort(500, 'IC_WEBHOOK_TOKEN must be at least 32 characters.');
        }
        // Compare the path token in constant time.
        if (!hash_equals($expected, $token)) {
            return response('', 404);
        }

        try {
            $event = Webhooks::parseAfterCallEvent($request->getContent());
        } catch (PayloadTooLargeError) {
            return response('', 413);
        } catch (WebhookPayloadError) {
            return response('', 400);
        }
        if ($event instanceof UnknownEvent) {
            return response('', 204); // an event type this receiver does not handle
        }

        // Cache::add is atomic: true only for the first delivery of this call id.
        if (!$event->isTest && Cache::add('ic:after-call:' . $event->callId, 1, now()->addDays(2))) {
            // Queue the call id only: the job reads the call with getCall($callId), so no names,
            // numbers or summaries sit in the queue or in failed jobs.
            ProcessAfterCallEvent::dispatch($event->callId);
        }

        return response('', 204);
    }
}
