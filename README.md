# Intelligence Cloud PHP SDK

PHP client for the Intelligence Cloud partner API (`https://app.intelligence.cloud/api/v1`).

- **Customer lifecycle** with admin credentials (a reseller admin login): companies, locations,
  users, products, connectors, First Alert, Insights Hub, and the Base Intelligence Seat workflows.
- **Conversation intelligence** with frontline credentials (a frontline user login): calls,
  transcripts, recordings, alerts, tasks, insights, and the After Call webhook.

Version 0.1.0. PHP 8.2 or later, Composer, Guzzle 7. The API reference is at
https://docs.intelligence.cloud/reference.html and the contract is
https://docs.intelligence.cloud/openapi.yaml.

## Contents

1. [Install](#install)
2. [Credentials](#credentials)
3. [Quick start: customer lifecycle](#quick-start-customer-lifecycle)
4. [Quick start: conversation intelligence](#quick-start-conversation-intelligence)
5. [Rules the SDK applies for you](#rules-the-sdk-applies-for-you)
6. [Filters and paging](#filters-and-paging)
7. [Errors](#errors)
8. [Webhooks (plain PHP and Laravel)](#webhooks)
9. [Method list](#method-list)
10. [Examples](#examples)

## Install

```bash
composer require intelligence-cloud/sdk
```

Alternative, from the source download: unzip it into your project and add it as a Composer path
repository:

```bash
unzip php.zip -d vendor-src
composer config repositories.intelligence-cloud path vendor-src/php
composer require intelligence-cloud/sdk:0.1.0
```

Required extensions: `json`, plus what Guzzle needs (`curl` recommended).

## Credentials

Today every integration signs in with the email and password of a **dedicated integration user**.
Turn MFA off for that user's company (`mfaOff: true` in `provisionSeatCustomer`, or
`$ic->companies->updateCompany($id, ['mfa_enforced' => false])`). **API keys arrive in December 2026.**

Use one client per credential:

| Credential | Role | Use it for |
|---|---|---|
| Admin | `reseller_admin` | `$ic->companies`, `$ic->users`, … and `$ic->workflows` |
| Frontline | `frontline_user` | `$ic->intelligence` (calls, alerts, tasks, insights) |

Admin logins get empty lists from the conversation-data endpoints by design. `$ic->intelligence`
checks the role once and throws `FrontlineRequiredError` instead of returning empty data.

Keep credentials out of source code. Read them from your secret store or environment. Send secrets
to Tresic only through **security@tresic.cloud**, and report security issues there too.

```php
use IntelligenceCloud\Client;

$admin = new Client(email: getenv('IC_ADMIN_EMAIL'), password: getenv('IC_ADMIN_PASSWORD'));
$data  = new Client(email: getenv('IC_DATA_EMAIL'), password: getenv('IC_DATA_PASSWORD'),
                    userAgent: 'riverbend-bi-sync/1.0');
```

Constructor: `new Client(email, password, baseUrl = 'https://app.intelligence.cloud/api/v1',
tokenStore = null, timeout = 30, httpClient = null, userAgent = null, sleeper = null,
maxRetries = 3, connectorTimeout = 600, loginRateLimitWait = 60)`. `userAgent` is sent ahead of
the SDK's own; `httpClient` is any Guzzle `ClientInterface`. If you pass your own HTTP client, keep
certificate verification on. The client never follows redirects, and refuses a `baseUrl` that is
not `https://` (except on localhost).

### Sign-in, tokens and sign-out

- The client signs in on first use and keeps the access and refresh tokens in its token store
  (memory by default).
- Sign-in is limited to 5 per minute. Sign in once per job and reuse the client. On a 429 at
  sign-in the client waits `loginRateLimitWait` seconds (60), once. Inside a web request pass
  `loginRateLimitWait: 0` to get a `RateLimitError` at once instead.
- A 429 on renewal is retried with the same refresh token (it was not used up); the client never
  signs in again because of it.
- The client renews the access token a minute before it expires, and once after a 401. Refresh
  tokens are single use: a spent one is never sent again, and the new pair is stored before it is used.
- `$ic->close()` forgets the tokens locally. It does **not** sign out.
- `$ic->logout()` signs out and **ends every session of that user, including the web app**. The SDK
  never calls it by itself.
- Sign-in returning `mfa_required` throws `MfaRequiredError`: "Integration users need MFA off for
  their company. API keys arrive in December 2026."
- Sign-in returning `password_expired: true` throws `PasswordExpiredError`: a person signs in to the
  app as the integration user and sets a new password; then update your secret store.

**Several workers, one integration user.** A refresh token sent twice ends the session everywhere.
The default `InMemoryTokenStore` is right for one process. When PHP-FPM workers or cron jobs share
an integration user, use `FileTokenStore`. It keeps the tokens in a 0600 file and serialises sign-in
and renewal with a file lock:

```php
use IntelligenceCloud\Auth\FileTokenStore;

$data = new Client(
    email: getenv('IC_DATA_EMAIL'),
    password: getenv('IC_DATA_PASSWORD'),
    tokenStore: new FileTokenStore('/var/lib/myapp/ic-tokens.json'), // outside the web root
);
```

To keep tokens in a shared store (a database or cache), implement
`IntelligenceCloud\Auth\TokenStore` (`get`, `set`, `withLock`). Store tokens like passwords.

## Quick start: customer lifecycle

A Base Intelligence Seat customer is one location with two user types:

- **End user**: After Call on their own line.
- **Manager**: After Call on all lines, First Alert, and Insights Hub.

The location gets two groups ("Manager Alerts", "Insights Hub Access") and two alerts routed to
Manager Alerts:

- **Upset Customer**: sentiment below −40.
- **Customer Threat**: the customer-threat phrases with sentiment below −20.

**Customer Threat phrases are provided by your Tresic contact.** Pass them in `threatPhrases`.

```php
use IntelligenceCloud\Lifecycle\{ConnectorSpec, LocationSpec, SeatCustomerSpec, UserSpec};

$customer = $admin->workflows->provisionSeatCustomer(new SeatCustomerSpec(
    companyName: 'Riverbend Auto Group',
    adminName: 'Pat Lee', adminEmail: 'pat.lee@example.com', adminPhone: '+15555550100',
    location: new LocationSpec('Riverbend - Main', '1 River Rd', 'Springfield', 'IL', 'United States', '62701',
                               vertical: 'csps-msps'),            // vertical id or key from listVerticals()
    threatPhrases: $customerThreatPhrases,                       // from your Tresic contact
    mfaOff: true,                                                // the company has an integration user
    connector: new ConnectorSpec('https://pbx.example.com/ns-api/v2', 'riverbend.example.com', $phoneSystemKey),
    ssoDomain: 'riverbend.example.com',                          // optional: returns the DNS TXT record
));

// End users first: each line is mapped, then the invitation is emailed.
$admin->workflows->addFrontlineUser($customer, new UserSpec('sam.ortiz@example.com', 'Sam', 'Ortiz'), '2101');
// The first manager switches the two alerts on, then gets the invitation.
$admin->workflows->addManager($customer, new UserSpec('dana.cho@example.com', 'Dana', 'Cho'));
```

### Seat workflows

`$ic->workflows`. Every workflow is safe to run again: it finds existing objects by name or email
and carries on.

| Workflow | What it does |
|---|---|
| `provisionSeatCustomer($spec)` | Company (`companyId` from an earlier run, or a same-named company whose primary contact email also matches; a same-named company with another contact throws `ConflictError` code `company_name_conflict`) with all three products → MFA off (`mfaOff`) → location (no default rule pack) → groups → both rules (switched off) → connector (optional) → Insights Hub group access and all-numbers coverage → SSO domain claim (optional). Returns a `SeatCustomer` with every id, plus `created` and `existing`. A connector that ends in `error` is recorded on the result (`connectorStatus`, `connectorStateReason`, e.g. `invalid_permissions` when the key lacks permissions: use a read/write key), not thrown. An inactive company or location found by name is an error unless `reactivate: true`. The connector is found by its NetSapiens domain (else its name); one in `error` or `inactive`, or with changed settings, gets the spec's settings and key and is restarted. |
| `getSeatCustomer($companyId)` | Rebuilds the `SeatCustomer` from what is configured. |
| `addFrontlineUser($customer, $user, $extension)` | Creates the end user (no invitation yet; After Call only, alert email off), maps their extension, adds it to every manager and integration user, First Alert and Insights Hub, then emails the invitation. If the user's mapping is refused it stops there with `ExtensionMappingError` and no invitation has been sent. |
| `addManager($customer, $user, $activateAlerts = true)` | Creates the manager (no invitation yet; all three products, both groups, alert email on every rule), maps all end-user lines at the location, switches both alerts on, then emails the invitation. The manager's own phone line is not captured on the Seat: it is never mapped, nor added to First Alert or Insights Hub. |
| `activateSeatAlerts($customer)` | Switches on the Upset Customer and Customer Threat rules. Returns the ids it switched on. |
| `addIntegrationUser($customer, $user)` | Creates the user an integration signs in with (password sign-in, tagged `department: "integration"`, all three products, both groups, every alert email off), maps all lines, then emails the invitation. Never switches the alerts on. |
| `changeUserExtension($customer, $userId, $old, $new)` | Moves an end user's line and re-syncs every manager and integration user, First Alert and Insights Hub. |
| `removeUser($customer, $userId, $keepExtensions = false)` | Takes the user out of every group, clears their number mapping, then deactivates them. Only an end user's line is released, and only when no other active end user holds it: it comes off managers, integration users, First Alert and Insights Hub. Removing a manager or an integration user (tagged, or any non-manager holding more than one line) releases nothing. Products are left as they are. Returns `kind`: `frontline`, `manager` or `integration`. |
| `listFrontlineUsers($customer)` / `listManagers($customer)` / `listIntegrationUsers($customer)` | Active users at the location: end users have After Call only; managers have all three products; integration users are tagged `department: "integration"` and are never end users or managers. |
| `syncNumbers($customer, $dryRun = false)` | Recomputes the lines (the number of every end user holding exactly one) and pushes them to managers, integration users, First Alert and Insights Hub. Managers and integration users get exactly the lines; any other number on their mapping is removed. `dryRun: true` returns the changes only. |
| `offboardCustomer($customer, $deactivateCompany = true)` | A customer leaves: pauses the After Call webhook (URL kept), clears the First Alert webhook (numbers kept), stops the connector, deactivates the integration users, then deactivates the company. Ask Tresic about retention of the customer's data. |

Things to know:

- **Store the returned `companyId` and pass it on every re-run** (`new SeatCustomerSpec(..., companyId: $id)`).
  Company names are not unique.
- **Integration users are not seats.** Add them with `addIntegrationUser`, or tag an existing one
  with `$admin->users->updateCompanyUser($companyId, $userId, ['department' => 'integration'])`.
  To make the workflows ignore other users, set `$customer->excludeUserIds` or
  `$customer->excludeEmails`.
- Both alert rules are created **switched off**. `addManager` switches them on once the manager is
  set up (`activateAlerts: true`, the default). To switch them on yourself, pass
  `activateAlerts: false` and call `activateSeatAlerts($customer)` when you are ready. Re-running
  `provisionSeatCustomer` never switches a rule off.
- Users are created **without** an invitation. The workflow maps their lines and sets their products
  and groups, and only then emails the invitation. If a mapping is refused, `ExtensionMappingError`
  is thrown and no invitation has been sent; run the workflow again once the connector is active and
  it finishes the setup and sends the invitation (it also re-sends one to any user who has not
  accepted yet). Pass `new UserSpec(..., sendInvitation: false)` to invite later with
  `$admin->users->resendInvitation($companyId, $userId)`.
- The company's primary contact is always emailed an invitation.
- Number mapping needs the connector to be `active`. The key must be read/write.
- An extension belongs to exactly one location. First Alert and Insights Hub numbers don't check
  other locations, so keep each extension at one location yourself.
- Phone-system extensions come from the phone system: read them there with your own key. This API
  cannot list them.
- Companies and locations are never deleted; deactivate them. Connectors are stopped with
  `deactivateConnector`.

## Quick start: conversation intelligence

```php
$intel = $data->intelligence;
$intel->requireFrontline();

// One page of calls with a phone number in any format (partial digits match too).
$page = $intel->searchCalls(phone: '+1 (555) 555-0142', dateFrom: new DateTimeImmutable('-30 days'));
echo $page->total;
foreach ($page as $call) {
    echo $call['call_date'], ' ', $call['sentiment_label'], ' ', $call['top_highlight'], "\n";
}

// Every negative call this week, across all pages.
foreach ($intel->iterCalls(sentiment: 'negative', dateFrom: new DateTimeImmutable('monday this week')) as $call) {
    // …
}

$call  = $intel->getCall($callId);            // summary, analysis, sentiment, tasks
$lines = $intel->getTranscript($callId);      // [{time, speaker: "Speaker 1", name, text}, …]
$rec   = $intel->getRecordingUrl($callId);    // RecordingLink {url, expiresAt} or null

$alerts = $intel->searchAlerts(alertName: 'Customer Threat', dateFrom: new DateTimeImmutable('-1 day'));
$alert  = $intel->getAlert($alertId, withCall: true);       // the call under 'call'

$tasks = $intel->listTasks(status: 'overdue', todayStart: '2026-11-02T00:00:00-06:00');
$dash  = $intel->getInsightsDashboard(new DateTimeImmutable('-7 days'), new DateTimeImmutable());
```

### Recordings

`getRecordingUrl()` returns only the link and its expiry. The link is a signed HTTPS URL to a WAV
file. Fetch it with **no** Authorization header; Range requests work. It is valid for 120 minutes,
so ask for a fresh one when you need it. Anyone holding it can download the audio: treat it as a
secret and never log it.

### Calls and alerts as they happen

New calls arrive on the After Call webhook and alerts arrive on the First Alert webhook as they fire
(see [Webhooks](#webhooks)). Don't poll the API for new data: read it when a person asks for
something or when a webhook tells you to fetch details, and run scheduled jobs (exports, digests)
at most once a day over a bounded date window.

## Rules the SDK applies for you

- **Filters:** list methods accept only the filters the API supports. The API ignores unknown
  parameters silently, so the SDK refuses them before sending (`FilterError`). Enums and ranges are
  checked; dates must be full timestamps; `numbers` takes up to 25 entries and strips formatting
  (a North American number is sent as its last 10 digits: `+1 555-555-0142` becomes `5555550142`);
  `location_id` lists take up to 50 ids.
- **Full-replacement writes** (group members, a user's groups, locations and number mappings,
  First Alert monitored numbers, Insights Hub numbers and groups, company and location products,
  rule groups) have `add…` / `remove…` / `set…` helpers that read, merge and write. The First Alert
  helpers always resend the webhook URL so it is not cleared; `saveFirstAlertConfig` refuses a body
  without `webhook_url`.
- **User create** always sends an `Idempotency-Key`, a new UUID unless you pass one, reused on
  retries. If the create returns 422 but left the user behind, the SDK finds the user by email and
  applies `location_ids`.
- **Connectors:** `createConnector` and `reactivateConnector` wait for `active`, `deactivateConnector`
  waits for `inactive`, checking every 10 seconds for up to 10 minutes (`connectorTimeout` on the
  client, `$timeout` per call; `wait: false` returns at once). `error` throws `ConnectorError` with
  `$stateReason`; no final status in time throws `ConnectorTimeoutError`.
- **Responses** carry only the fields in the published reference. Anything else is dropped. The
  one-time sign-in link a user create can return is never passed through or logged.
- **Test endpoints** (connector dry run, webhook tests) report only success and the HTTP status.
- **Retries:** 429 is retried up to 3 times, honouring `Retry-After` up to 60 seconds (a longer
  `Retry-After` throws `RateLimitError` with `$retryAfter` at once). 5xx is retried up to 3 times,
  with backoff and jitter, for GET, PATCH, PUT, DELETE and user create; never for other creates.

## Filters and paging

Low-level list methods take filters by their API names, exactly as in the reference:

```php
$page = $admin->users->listCompanyUsers($companyId, ['status' => 'active', 'limit' => 100]);
$next = $admin->users->listCompanyUsers($companyId, ['status' => 'active', 'limit' => 100, 'cursor' => $page->nextCursor]);

foreach ($admin->users->iterCompanyUsers($companyId, ['status' => 'active']) as $user) {
    echo $user['email'], "\n";
}
```

A `Page` has `items` (records with the `type`/`attributes` wrapper removed), `hasMore`,
`nextCursor`, `total`, `summary` (call, alert and task totals for the whole filter) and `raw`. It is
iterable and countable. Every list method has an `iter…` twin that follows cursors 100 at a time.
Single reads and writes return the response body as documented; `Client::unwrap($body)` returns the
record inside.

Default windows: calls, alerts and topic calls start at 00:00 UTC seven days ago. Tasks have no
default window. The Insights Hub dashboard needs `start` and `end`.

## Errors

Every error extends `IntelligenceCloud\Exception\IntelligenceCloudError`.

An API error's text is `HTTP <status> <code> (request_id <id>)` plus text the SDK wrote itself; the
server's own message is never in it (it is on `$e->serverMessage`, which can contain numbers or
names: don't log it). Log status, code and request_id, not exception text or response bodies.

| Class | When |
|---|---|
| `ApiError` | Any error response: `$status`, `$errorCode` (normalised from the API's error shapes), `$requestId`, `$operationId`. |
| `AuthError` | 401 after one renewal, or a refused sign-in. |
| `MfaRequiredError` | The integration user has MFA on. |
| `PasswordExpiredError` | The integration user's password has expired (an `AuthError`). |
| `NotFoundError` | 404. Also returned for records outside your login's scope: treat them as absent. |
| `ConflictError` | 409: duplicate name or email, already active or inactive. For an extension already monitored at another location the text says "Extension is already monitored at another location." and `$extensions` lists the extensions. `provisionSeatCustomer` throws it with `$errorCode` `company_name_conflict` for a same-named company with another primary contact. |
| `ValidationError` | 400 or 422. |
| `RateLimitError` | 429 after retries; `$retryAfter` in seconds. |
| `FilterError` | Unsupported filter or field, or a bad value. Thrown before anything is sent. |
| `ConnectorError` | A connector ended in `error`: `$stateReason`, `$connectorStatus`, `$connectorId`, `$statusMessage`. |
| `ConnectorTimeoutError` | A connector did not settle in time (a `ConnectorError`). |
| `ExtensionMappingError` | A workflow could not map a user's numbers: `$userId`, `cause()`. Nothing after it was changed. |
| `FrontlineRequiredError` | `$ic->intelligence` was used with an admin login. |
| `WebhookPayloadError` | The webhook body is not a JSON object, or a malformed After Call event. |
| `PayloadTooLargeError` | The webhook body is over `Webhooks::MAX_EVENT_BYTES` (256 KB); answer 413. |

```php
use IntelligenceCloud\Exception\{ConflictError, NotFoundError, ValidationError};

try {
    $admin->users->setNumbers($companyId, $userId, $connectorId, ['2101']);
} catch (ConflictError $e) {
    // Release $e->extensions at the other location first.
} catch (ValidationError $e) {
    // e.g. the connector is not active yet
} catch (NotFoundError $e) {
    // the user or connector is gone, or not yours
}
```

Quote `$e->requestId` when you contact support.

## Webhooks

Webhooks are how your integration hears about new calls and alerts: the After Call webhook for every
call, the First Alert webhook for each alert as it fires. Subscribe to them; don't poll the API for
new data.

### Configure (admin)

The After Call webhook sends one event per call (`type: end_of_call_summary`) to one HTTPS URL per
location, for **every call at the location**.

```php
$admin->intelligence->setAfterCallWebhook($locationId, 'https://hooks.example.com/ic/' . getenv('IC_WEBHOOK_TOKEN') . '/after-call');
$result = $admin->intelligence->testAfterCallWebhook($locationId);   // WebhookTestResult {success, httpStatus}
```

There is no delete: pause with `setAfterCallWebhook($locationId, $url, enabled: false)`.

The First Alert webhook is set in the location's First Alert configuration, keeping the monitored
numbers (read-merge-write):

```php
$admin->intelligence->setFirstAlertWebhook($locationId, 'https://hooks.example.com/ic/' . getenv('IC_ALERT_WEBHOOK_TOKEN') . '/alerts');
$result = $admin->intelligence->testFirstAlertWebhook($locationId);  // WebhookTestResult {success, httpStatus}
```

### Receive

- **HTTPS, with a long, unguessable path token** from the environment (`IC_WEBHOOK_TOKEN`, at least
  32 characters; make one with `php -r "echo bin2hex(random_bytes(32));"`), compared in constant
  time (`hash_equals`). Deliveries are not signed, so the URL is the secret. Keep it out of logs:
  redact the path in your web server, proxy, load balancer and APM.
- **Answer 2xx within 2 seconds**, then process asynchronously. Redirects count as failures.
- **Answer 2xx to event types you don't handle** (`parseAfterCallEvent` returns an `UnknownEvent`).
  Answer 400 only for a body that isn't a JSON object, 413 for one over 256 KB.
- **Deliveries repeat** (retries, calls with several legs). Store the call id first, then process:
  upsert on `call.id`, and when an id repeats re-read the call.
- **Treat an event as a notification.** Before you write anything from it into another system, read
  `$ic->intelligence->getCall($event->callId)` with your frontline integration user and use that.
- **Run a once-a-day catch-up** over the last 48 hours with `iterCalls()`, skipping ids you've
  handled. A delivery is dropped after three attempts and there is no replay (see
  `examples/nightly_catch_up.php`). The catch-up sees only calls on lines mapped to its user: map
  every line the location captures.
- Test locally by posting `examples/after-call-sample.json` to your receiver.

Plain PHP (`examples/webhook_receiver.php`):

```php
use IntelligenceCloud\Exception\{PayloadTooLargeError, WebhookPayloadError};
use IntelligenceCloud\Webhook\{Deduplicator, PdoDedupStore, UnknownEvent, Webhooks};

$token = (string) getenv('IC_WEBHOOK_TOKEN');       // at least 32 characters
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (strlen($token) < 32 || !hash_equals('/ic/' . $token . '/after-call', $path)) {   // constant time
    http_response_code(404);
    exit;
}
try {
    $event = Webhooks::parseAfterCallEvent((string) file_get_contents('php://input', false, null, 0, Webhooks::MAX_EVENT_BYTES + 1));
} catch (PayloadTooLargeError) {
    http_response_code(413);
    exit;
} catch (WebhookPayloadError) {
    http_response_code(400);
    exit;
}
if ($event instanceof UnknownEvent || $event->isTest) {
    http_response_code(204);
    exit;
}
// The shared store keeps the call ids for every worker and across restarts; your worker reads them.
$dedup = new Deduplicator(new PdoDedupStore(new PDO(getenv('IC_QUEUE_DSN')), 'ic_after_call_queue'));
$dedup->isDuplicate($event);                        // stores the id the first time; a repeat is dropped
http_response_code(204);
```

Laravel (`examples/laravel/AfterCallWebhookController.php`):

```php
// routes/api.php
Route::post('/ic/{token}/after-call', AfterCallWebhookController::class);

// app/Http/Controllers/AfterCallWebhookController.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use IntelligenceCloud\Exception\{PayloadTooLargeError, WebhookPayloadError};
use IntelligenceCloud\Webhook\{UnknownEvent, Webhooks};

class AfterCallWebhookController
{
    public function __invoke(Request $request, string $token)
    {
        // services.intelligence_cloud.webhook_token = env('IC_WEBHOOK_TOKEN'), at least 32 characters
        if (!hash_equals((string) config('services.intelligence_cloud.webhook_token'), $token)) {
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
            return response('', 204);
        }
        // Cache::add is atomic: true only for the first delivery of this call id.
        if (!$event->isTest && Cache::add('ic:after-call:' . $event->callId, 1, now()->addDays(2))) {
            ProcessAfterCallEvent::dispatch($event->callId);   // the call id only; the job reads the call
        }
        return response('', 204);
    }
}
```

`Deduplicator::isDuplicate($eventOrId)` returns true for a repeat and records a first sighting (the
same name and meaning in every Intelligence Cloud SDK; `isNew` is its deprecated opposite).
`PdoDedupStore` keeps the ids in a database table shared by every worker.

`AfterCallEvent` has `callId`, `locationId`, `timestamp`, `isTest`, `call`, `summary`, `location`,
`raw`, and `callType()`, `callerNumber()`, `calleeNumber()`, `durationSeconds()`, `summaryText()`,
`sentiment()`.

### Receive First Alert

The First Alert webhook posts the alert that fired: the alert id, the rule, the location, the call
and the time. Read only the alert id, answer 2xx at once, then fetch the alert and its call with a
frontline integration user in Manager Alerts (`examples/first_alert_webhook_receiver.php`):

```php
$alertId = alertIdFrom((string) file_get_contents('php://input', false, null, 0, 65536));  // see the example
if ($alertId === null) {
    http_response_code(204);                       // nothing this receiver handles
    exit;
}
$repeat = $alertDedup->isDuplicate($alertId);      // a second Deduplicator over a shared store, keyed on the alert id
http_response_code(204);
fastcgi_finish_request();                          // answer now, work afterwards (PHP-FPM)

if (!$repeat) {
    $alert = $data->intelligence->getAlert($alertId, withCall: true);   // null when not visible
    if ($alert !== null) {
        pageOnCallManager($alert['id'], $alert['call_id']);             // ids only; never log the summary
    }
}
```

If your receiver was unavailable, read the window you missed once and skip alerts you have already
handled. Alerts are dated by their call, so start the window 30 minutes before the receiver went down:

```php
foreach ($data->intelligence->iterAlerts(dateFrom: $wentDown->modify('-30 minutes'), dateTo: $cameBack) as $alert) {
    if (!$alertDedup->isDuplicate($alert['id'])) {
        handleAlert($alert['id']);
    }
}
```

## Method list

Every API operation is a method named after its `operationId`, grouped by reference section. Path
ids come first, then the request body as an array with the field names from the reference, then
filters as an array.

| Namespace | Methods |
|---|---|
| `$ic->resellers` | `getResellerBranding`, `updateResellerBranding`, `getResellerSmtpSettings`, `saveResellerSmtpSettings`, `deleteResellerSmtpSettings` |
| `$ic->companies` | `listCompanies` / `iterCompanies`, `createCompany`, `getCompaniesMfaSummary`, `getCompany`, `updateCompany`, `deactivateCompany`, `reactivateCompany`, `getCompanyBranding`, `updateCompanyBranding`; helpers `addProducts`, `removeProducts`, `findCompanies`, `findCompany` |
| `$ic->sso` | `listCompanyDomains`, `claimCompanyDomain`, `removeCompanyDomain`, `listDomainVerifications`, `verifyDomain`, `getIdentityProvider`, `connectIdentityProvider`, `disconnectIdentityProvider`, `testIdentityProvider` |
| `$ic->products` | `listProducts`, `listCompanyProducts`, `listUserProducts`, `enableUserProduct`, `disableUserProduct` |
| `$ic->locations` | `listVerticals`, `listCompanyLocations` / `iterCompanyLocations`, `createLocation`, `getLocation`, `updateLocation`, `deactivateLocation`, `reactivateLocation`; helpers `addProducts`, `removeProducts`, `findLocation` |
| `$ic->groups` | `listUserGroups` / `iterUserGroups`, `createUserGroup`, `getUserGroup`, `updateUserGroup`, `deleteUserGroup`, `getUserGroupMembership`, `setUserGroupMembership`; helpers `addMembers`, `removeMembers`, `addUserToGroups`, `removeUserFromGroups`, `userGroupIds`, `findGroup` |
| `$ic->connectors` | `listConnectorTypes`, `testConnector`, `listLocationConnectors` / `iterLocationConnectors`, `createConnector`, `getConnector`, `updateConnector`, `deactivateConnector`, `reactivateConnector`, `listCompanyConnectors` / `iterCompanyConnectors`; helpers `waitForStatus`, `findConnector` |
| `$ic->firstAlert` | `listAlertRules` / `iterAlertRules`, `createAlertRule`, `getAlertRule`, `updateAlertRule`, `deleteAlertRule`, `duplicateAlertRule`, `getFirstAlertConfig`, `saveFirstAlertConfig`, `testFirstAlertWebhook`, `getAlertNotificationPreferences`, `setAlertEmailPreferences`, `setAlertDigestPreferences`; helpers `addMonitoredNumbers`, `removeMonitoredNumbers`, `setMonitoredNumbers`, `addRuleGroups`, `removeRuleGroups`, `findRule` |
| `$ic->insightsHub` | `getInsightsHubSetup`, `saveInsightsHubSetup`, `updateInsightsHubSetup`, `getInsightsHubConfig`, `updateInsightsHubConfig`, `getUserInsightsHubAccess`; helpers `addNumbers`, `removeNumbers`, `setNumbers`, `addGroups`, `removeGroups` |
| `$ic->users` | `listCompanyUsers` / `iterCompanyUsers`, `createCompanyUser`, `getCompanyUser`, `updateCompanyUser`, `deactivateCompanyUser`, `reactivateCompanyUser`, `resendInvitation`, `resetPassword`, `getUserNumberMappings`, `setUserNumberMappings`, `listLocationUsers` / `iterLocationUsers`, `getLocationUser`; helpers `findUserByEmail`, `addLocations`, `removeLocations`, `userNumbers`, `addNumbers`, `removeNumbers`, `setNumbers` |
| `$ic->afterCallSettings` | `getUserAfterCallSettings`, `updateUserAfterCallSettings` |
| `$ic->account` | `getMe`, `listVisibleLocations` / `iterVisibleLocations` |
| `$ic->calls` | `listCalls` / `iterCalls`, `getCall`, `getCallTranscript` |
| `$ic->alerts` | `listAlerts` / `iterAlerts`, `getAlertsDashboard` |
| `$ic->tasks` | `listTasks` / `iterTasks` |
| `$ic->insights` | `getInsightsDashboard`, `listTopicCalls` / `iterTopicCalls` |
| `$ic->webhooks` | `getAfterCallWebhook`, `saveAfterCallWebhook`, `testAfterCallWebhook` |
| `$ic->intelligence` | `whoami`, `requireFrontline`, `searchCalls` / `iterCalls`, `getCall`, `getTranscript`, `getRecordingUrl`, `searchAlerts` / `iterAlerts`, `getAlert($alertId, withCall: false)`, `getAlertsDashboard`, `listTasks` / `iterTasks`, `getInsightsDashboard`, `listTopicCalls` / `iterTopicCalls`, `getAfterCallWebhook`, `setAfterCallWebhook`, `testAfterCallWebhook`, `getFirstAlertWebhook`, `setFirstAlertWebhook`, `testFirstAlertWebhook` |
| `$ic->workflows` | see [Seat workflows](#seat-workflows) |
| `$ic` | `logout`, `close`, `call($operationId, $path, $query, $body)`, `Client::unwrap($body)` |

Also: `Webhooks::parseAfterCallEvent`, `UnknownEvent`, `Deduplicator`, `DedupStore`, `InMemoryDedupStore`, `PdoDedupStore`, `Page`,
the error classes, the lifecycle spec classes (`SeatCustomerSpec`, `LocationSpec`, `ConnectorSpec`,
`UserSpec`) and `SeatDefaults` (group and rule names and thresholds).

The `$ic->intelligence` search helpers take named arguments:
`searchCalls(phone:, text:, dateFrom:, dateTo:, status:, sentiment:, hasOpenTasks:, multiLeg:, locationIds:, companyId:, limit:, cursor:)`
and `searchAlerts(alertName:, numbers:, text:, sentiment:, dateFrom:, dateTo:, locationIds:, topic:, companyId:, limit:, cursor:)`.
`phone` and `text` both map to the API's one search field: pass one or the other.

## Examples

| File | What |
|---|---|
| `examples/provision_seat_customer.php` | Provision a Seat customer |
| `examples/manage_users.php` | Add, move and remove users |
| `examples/search_calls.php` | Calls, transcript, recording link (prints counts and ids only) |
| `examples/webhook_receiver.php` | Plain PHP After Call receiver |
| `examples/first_alert_webhook_receiver.php` | Plain PHP First Alert receiver that fetches each alert and its call |
| `examples/laravel/AfterCallWebhookController.php` | Laravel receiver |
| `examples/nightly_catch_up.php` | Once-a-day catch-up over the last 48 hours |
| `examples/after-call-sample.json` | A sample After Call event to post to your receiver locally |

The admin examples read `IC_ADMIN_EMAIL` and `IC_ADMIN_PASSWORD`; the frontline examples read
`IC_DATA_EMAIL` and `IC_DATA_PASSWORD`. The receivers read `IC_WEBHOOK_TOKEN` /
`IC_ALERT_WEBHOOK_TOKEN` (at least 32 characters) and `IC_QUEUE_DSN` (a PDO DSN for the shared
store).

## License

Intelligence Cloud SDK Licence. Copyright (c) 2026 Creo Solutions, Inc. d/b/a Tresic. Partners, their customers and contractors working for either may use, copy, modify and distribute the SDK solely to access Intelligence Cloud. Use of the API is governed by the Intelligence Cloud API and SDK Terms (https://docs.intelligence.cloud/terms/api) and your agreement with Tresic or your provider. Provided as is. See `LICENSE`.
