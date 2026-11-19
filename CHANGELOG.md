# Changelog

## 0.1.0

### 2026-11-18 (Base Intelligence Seat manager lines)

- On the Base Intelligence Seat a manager's own phone line is not captured.
  `addManager($customer, $user, $activateAlerts = true)` no longer takes `$ownExtension`: a manager
  is mapped to exactly the end-user lines at the location, and their own extension is never mapped,
  nor added to First Alert or Insights Hub.
- `syncNumbers` sets every manager's mapping to exactly the lines (as for integration users); a
  number on a manager that is not a line is removed.

### 2026-11-17 (review 2)

- Licence: the Intelligence Cloud SDK Licence (Copyright (c) 2026 Creo Solutions, Inc. d/b/a Tresic), the same text in every SDK: partners, their customers and contractors may use, copy, modify and distribute the SDK solely to access Intelligence Cloud; provided as is.
- `provisionSeatCustomer` never adopts a company found by name alone: pass `companyId` (new on
  `SeatCustomerSpec`), or the name and the primary contact email must both match. A same-named
  company with another contact throws `ConflictError` (`$errorCode` `company_name_conflict`) before
  anything is written. New `$ic->companies->findCompanies()`.
- The connector is found by its NetSapiens domain (else its name). One in `error` or `inactive`, or
  with changed settings, gets the spec's settings and key (`updateConnector`) and is restarted.
- Integration users: new `addIntegrationUser`, `listIntegrationUsers` and `Workflows::isIntegration`
  (company-user `department: "integration"`). They are never end users or managers and are kept on
  all lines like managers. `removeUser` releases only an end user's line, and only when no other
  active end user holds it; removing an integration user (or any non-manager holding several lines)
  releases nothing. Lines count only end users holding exactly one number. A manager has all three
  products. `SeatCustomer::$excludeUserIds` / `$excludeEmails` hide users from every workflow.
  `syncNumbers` reports `integration_users`.
- New `offboardCustomer`: pause the After Call webhook, clear the First Alert webhook, stop the
  connector, deactivate integration users, deactivate the company.
- `Webhooks::parseAfterCallEvent` returns `UnknownEvent` for other event types, and takes
  `$maxBytes` (default `Webhooks::MAX_EVENT_BYTES`, 256 KB; larger throws `PayloadTooLargeError`).
  New `PdoDedupStore` (a shared, persistent `DedupStore`). `Deduplicator::isNew` is deprecated in
  favour of `isDuplicate`.
- Exception text never carries the server's message (`$serverMessage` holds it). New
  `PasswordExpiredError`.
- `Retry-After` is honoured up to 60 seconds; longer throws `RateLimitError`. A 429 on renewal
  retries the same refresh token instead of signing in. New `loginRateLimitWait` option (default 60).
- No redirects are followed (Guzzle `allow_redirects: false`); a non-https `baseUrl` is refused
  outside localhost.
- Alert `numbers`: a North American number is sent as its last 10 digits.
- PHP 8.2 or later. Examples read path tokens from `IC_WEBHOOK_TOKEN` / `IC_ALERT_WEBHOOK_TOKEN`
  (32 characters or more), answer 413 / 2xx for unknown types, use the shared store, and print or
  queue ids only. `examples/after-call-sample.json` added. The `test-live` Composer script is gone.

### First release

- `Client`: sign-in with an integration user, token renewal with single-use refresh tokens, a
  file-locked token store for several workers, retries with backoff on 429 and 5xx, one error
  hierarchy, and response projection to the published schema.
- Every documented API operation as a method named after its `operationId`, grouped by reference
  section (`$ic->companies`, `$ic->users`, …), with an `iter…` twin for every list and
  read-merge-write helpers for full-replacement writes.
- Client-side checks: unknown filters, date-only values, more than 25 `numbers` and more than 50
  `location_id` values are refused before the request is sent.
- `$ic->workflows`: `provisionSeatCustomer`, `getSeatCustomer`, `addFrontlineUser`, `addManager`,
  `changeUserExtension`, `removeUser`, `listFrontlineUsers`, `listManagers`, `syncNumbers`.
- `$ic->intelligence`: calls, transcripts, recording links, alerts, tasks, insights, and webhook
  configuration.
- Webhook receiver helpers: `Webhooks::parseAfterCallEvent`, `Deduplicator`.
- Connector create, reactivate and deactivate wait for the final status, checking every 10 seconds
  for up to 10 minutes (`connectorTimeout`, default 600; `ConnectorsApi::$checkInterval`).
- New calls and alerts reach you on the After Call and First Alert webhooks (2026-11-17).
  `examples/first_alert_webhook_receiver.php` is a First Alert receiver: it reads only the alert id,
  answers at once and fetches the alert and its call with `getAlert($alertId, withCall: true)`.
  The README covers a one-off catch-up after an outage.
