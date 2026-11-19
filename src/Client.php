<?php

declare(strict_types=1);

namespace IntelligenceCloud;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use IntelligenceCloud\Api\AccountApi;
use IntelligenceCloud\Api\AfterCallSettingsApi;
use IntelligenceCloud\Api\AlertsApi;
use IntelligenceCloud\Api\CallsApi;
use IntelligenceCloud\Api\CompaniesApi;
use IntelligenceCloud\Api\ConnectorsApi;
use IntelligenceCloud\Api\FirstAlertApi;
use IntelligenceCloud\Api\GroupsApi;
use IntelligenceCloud\Api\InsightsApi;
use IntelligenceCloud\Api\InsightsHubApi;
use IntelligenceCloud\Api\LocationsApi;
use IntelligenceCloud\Api\ProductsApi;
use IntelligenceCloud\Api\ResellersApi;
use IntelligenceCloud\Api\SsoApi;
use IntelligenceCloud\Api\TasksApi;
use IntelligenceCloud\Api\UsersApi;
use IntelligenceCloud\Api\WebhooksApi;
use IntelligenceCloud\Intelligence\Intelligence;
use IntelligenceCloud\Lifecycle\Workflows;
use IntelligenceCloud\Auth\InMemoryTokenStore;
use IntelligenceCloud\Auth\Tokens;
use IntelligenceCloud\Auth\TokenStore;
use IntelligenceCloud\Exception\ApiError;
use IntelligenceCloud\Exception\AuthError;
use IntelligenceCloud\Exception\ConflictError;
use IntelligenceCloud\Exception\FilterError;
use IntelligenceCloud\Exception\MfaRequiredError;
use IntelligenceCloud\Exception\NotFoundError;
use IntelligenceCloud\Exception\PasswordExpiredError;
use IntelligenceCloud\Exception\RateLimitError;
use IntelligenceCloud\Exception\ValidationError;
use IntelligenceCloud\Internal\Projector;
use IntelligenceCloud\Internal\Query;
use IntelligenceCloud\Internal\SpecTables;
use Psr\Http\Message\ResponseInterface;

/**
 * Intelligence Cloud partner API client. One client per credential.
 *
 *     $ic = new Client(email: getenv('IC_EMAIL'), password: getenv('IC_PASSWORD'));
 *     $me = $ic->intelligence->whoami();
 *
 * Operations are grouped by reference section: $ic->companies, $ic->locations, $ic->users, …
 * $ic->intelligence holds the conversation-intelligence helpers and $ic->workflows the Base
 * Intelligence Seat lifecycle.
 *
 * - Signs in on the first call and keeps the tokens in the token store (memory by default).
 * - Renews the access token before it expires and once after a 401. Refresh tokens are single
 *   use: the spent one is never sent again, and the new one is stored before it is used.
 * - Retries 429 (honouring Retry-After up to 60 seconds) and 5xx with backoff and jitter, up to 3
 *   times. 5xx is retried only for GET, PATCH, PUT, DELETE and user create (which is idempotent).
 *   A Retry-After over 60 seconds is not waited on: the error is thrown with `$retryAfter` set.
 * - A 429 on renewal retries the same refresh token (it was not used up); it never signs in instead.
 * - Never follows redirects, and refuses a base URL that is not https (localhost excepted).
 * - Drops every response field that is not in the published schema.
 * - Never calls logout on its own. logout() ends ALL of the user's sessions, web app included.
 */
final class Client
{
    public const VERSION = '0.1.0';
    public const DEFAULT_BASE_URL = 'https://app.intelligence.cloud/api/v1';

    /** Renew this many seconds before the access token expires. */
    private const RENEW_MARGIN = 60;
    /** Longest Retry-After the client waits on; anything longer is thrown as RateLimitError. */
    public const MAX_RETRY_AFTER = 60;

    private readonly string $baseUrl;
    private readonly TokenStore $tokenStore;
    private readonly ClientInterface $http;
    private readonly \Closure $sleeper;
    private readonly string $userAgent;

    /** Test endpoints: never pass on server error text. */
    private const REDACT_ERRORS = ['testConnector', 'testFirstAlertWebhook', 'testAfterCallWebhook', 'saveResellerSmtpSettings', 'testIdentityProvider'];
    /** Number-mapping writes: a 409 is the extension-conflict check; the SDK replaces its text. */
    private const EXTENSION_CONFLICT = ['setUserNumberMappings', 'updateUserAfterCallSettings'];
    /** POSTs that are safe to repeat on a 5xx. */
    private const RETRY_SAFE_POST = ['createCompanyUser'];

    public readonly ResellersApi $resellers;
    public readonly CompaniesApi $companies;
    public readonly SsoApi $sso;
    public readonly ProductsApi $products;
    public readonly LocationsApi $locations;
    public readonly GroupsApi $groups;
    public readonly ConnectorsApi $connectors;
    public readonly FirstAlertApi $firstAlert;
    public readonly InsightsHubApi $insightsHub;
    public readonly UsersApi $users;
    public readonly AfterCallSettingsApi $afterCallSettings;
    public readonly AccountApi $account;
    public readonly CallsApi $calls;
    public readonly AlertsApi $alerts;
    public readonly TasksApi $tasks;
    public readonly InsightsApi $insights;
    public readonly WebhooksApi $webhooks;
    public readonly Intelligence $intelligence;
    public readonly Workflows $workflows;

    public function __construct(
        private readonly string $email,
        #[\SensitiveParameter] private readonly string $password,
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?TokenStore $tokenStore = null,
        private readonly float $timeout = 30.0,
        ?ClientInterface $httpClient = null,
        ?string $userAgent = null,
        ?callable $sleeper = null,
        private readonly int $maxRetries = 3,
        /** Seconds to wait for a connector to settle after create, reactivate or deactivate. */
        private readonly float $connectorTimeout = 600.0,
        /**
         * Seconds to wait, once, when sign-in answers 429 (sign-in is limited to 5 per minute).
         * 0 throws RateLimitError at once, which suits code running inside a web request.
         */
        private readonly float $loginRateLimitWait = 60.0,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        self::checkBaseUrl($this->baseUrl);
        $this->tokenStore = $tokenStore ?? new InMemoryTokenStore();
        // A client you pass in must keep certificate verification on. Redirects are never followed.
        $this->http = $httpClient ?? new GuzzleClient(['allow_redirects' => false]);
        $this->sleeper = $sleeper !== null
            ? \Closure::fromCallable($sleeper)
            : static function (float $seconds): void {
                if ($seconds > 0) {
                    usleep((int) round($seconds * 1_000_000));
                }
            };
        $base = 'intelligence-cloud-php/' . self::VERSION;
        $this->userAgent = $userAgent !== null && $userAgent !== '' ? $userAgent . ' ' . $base : $base;

        $this->resellers = new ResellersApi($this);
        $this->companies = new CompaniesApi($this);
        $this->sso = new SsoApi($this);
        $this->products = new ProductsApi($this);
        $this->locations = new LocationsApi($this);
        $this->groups = new GroupsApi($this);
        $this->connectors = new ConnectorsApi($this);
        $this->firstAlert = new FirstAlertApi($this);
        $this->insightsHub = new InsightsHubApi($this);
        $this->users = new UsersApi($this);
        $this->afterCallSettings = new AfterCallSettingsApi($this);
        $this->account = new AccountApi($this);
        $this->calls = new CallsApi($this);
        $this->alerts = new AlertsApi($this);
        $this->tasks = new TasksApi($this);
        $this->insights = new InsightsApi($this);
        $this->webhooks = new WebhooksApi($this);
        $this->intelligence = new Intelligence($this);
        $this->workflows = new Workflows($this);
    }

    /** @return array<string,string> */
    public function __debugInfo(): array
    {
        return ['baseUrl' => $this->baseUrl, 'email' => $this->email, 'password' => '<redacted>'];
    }

    // ------------------------------------------------------------------ public core

    /**
     * Call one documented operation by its operationId and return the projected response body.
     *
     * @param array<string,string> $path   path parameters, e.g. ['companyId' => '…']
     * @param array<string,mixed>  $query  filters (checked against the spec)
     * @param array<string,mixed>|list<mixed>|null $body JSON body (top-level keys checked against the spec)
     * @param array<string,mixed>  $options internal: headers, retryHeaders, rawPick
     * @return array<string,mixed>|null null for 204 responses
     */
    public function call(string $operationId, array $path = [], array $query = [], ?array $body = null, array $options = []): ?array
    {
        $op = SpecTables::OPERATIONS[$operationId] ?? null;
        if ($op === null) {
            throw new FilterError(sprintf('Unknown operation "%s".', $operationId));
        }
        $url = $this->baseUrl . $this->buildPath($op['path'], $path, $operationId);
        $qs = Query::build($operationId, $op['query'], $query);
        if ($body !== null) {
            $this->checkBody($operationId, $op, $body);
        }
        $retrySafe = in_array($op['method'], ['GET', 'PUT', 'PATCH', 'DELETE'], true) || in_array($operationId, self::RETRY_SAFE_POST, true);
        $options['redactErrors'] = in_array($operationId, self::REDACT_ERRORS, true);
        $options['requestedNumbers'] = $body !== null ? self::requestedNumbers($body) : [];
        $options['extensionConflict'] = in_array($operationId, self::EXTENSION_CONFLICT, true) || $options['requestedNumbers'] !== [];
        [$status, $decoded] = $this->send(
            $op['method'],
            $url,
            $qs,
            $body,
            $options['headers'] ?? [],
            $retrySafe,
            $operationId,
            $options,
        );
        if ($status === 204 || $decoded === null) {
            return null;
        }
        if (($options['rawPick'] ?? null) !== null) {
            // Internal: pick named fields out of the raw body; nothing else is returned.
            return ($options['rawPick'])($decoded);
        }
        $projected = Projector::project($decoded, $op['resp']);
        return is_array($projected) ? $projected : null;
    }

    /**
     * Sign out. This ends ALL of this user's sessions, including the web app and any other job
     * using the same integration user. Most integrations never call it; the SDK never calls it
     * for you.
     */
    public function logout(): void
    {
        $this->call('logout');
        $this->tokenStore->set(null);
    }

    /**
     * Forget the tokens held by this client without signing out. Other sessions are untouched.
     */
    public function close(): void
    {
        $this->tokenStore->set(null);
    }

    // ------------------------------------------------------------------ paging

    /**
     * One page of a list operation.
     *
     * @internal used by the namespaces
     * @param array<string,string> $path
     * @param array<string,mixed> $filters
     */
    public function page(string $operationId, array $path, array $filters): Page
    {
        return Page::fromResponse($this->call($operationId, $path, $filters) ?? []);
    }

    /**
     * Follow cursors with limit=100 and yield every row (attributes unwrapped).
     *
     * @internal used by the namespaces
     * @param array<string,string> $path
     * @param array<string,mixed> $filters
     * @return \Generator<int, array<string,mixed>>
     */
    public function iterate(string $operationId, array $path, array $filters): \Generator
    {
        if (array_key_exists('cursor', $filters)) {
            throw new FilterError('Iterators follow cursors themselves: leave "cursor" out.');
        }
        // Check the filters now, so a bad filter fails at the call and not at the first foreach.
        Query::build($operationId, SpecTables::OPERATIONS[$operationId]['query'] ?? [], $filters);
        return $this->paginate($operationId, $path, $filters);
    }

    /** @return \Generator<int, array<string,mixed>> */
    private function paginate(string $operationId, array $path, array $filters): \Generator
    {
        $filters['limit'] ??= 100;
        $seen = [];
        while (true) {
            $page = $this->page($operationId, $path, $filters);
            foreach ($page->items as $item) {
                yield $item;
            }
            $next = $page->nextCursor;
            if (!$page->hasMore || $next === null || isset($seen[$next])) {
                return;
            }
            $seen[$next] = true;
            $filters['cursor'] = $next;
        }
    }

    /**
     * The record inside a response body: `data.attributes` when present, else `data`.
     *
     * @return mixed
     */
    public static function unwrap(mixed $body): mixed
    {
        if (is_array($body) && array_key_exists('data', $body)) {
            $d = $body['data'];
            if (is_array($d) && isset($d['attributes']) && is_array($d['attributes'])) {
                return $d['attributes'];
            }
            return $d;
        }
        return $body;
    }

    // ------------------------------------------------------------------ auth

    private function accessToken(): string
    {
        $t = $this->tokenStore->get();
        if ($t === null) {
            return $this->renew(null)->accessToken;
        }
        if ($t->expiresWithin(self::RENEW_MARGIN)) {
            return $this->renew($t->accessToken)->accessToken;
        }
        return $t->accessToken;
    }

    /**
     * Renew under the store lock. If another process already renewed (the stored access token is
     * no longer $stale), use its tokens.
     */
    private function renew(?string $stale): Tokens
    {
        return $this->tokenStore->withLock(function () use ($stale): Tokens {
            $cur = $this->tokenStore->get();
            if ($cur !== null && $cur->accessToken !== $stale && !$cur->expiresWithin(self::RENEW_MARGIN)) {
                return $cur;
            }
            if ($cur !== null && $cur->refreshToken !== null) {
                $rt = $cur->refreshToken;
                // Mark it spent before sending: a refresh token is never sent twice.
                $this->tokenStore->set($cur->withSpentRefreshToken());
                try {
                    $fresh = $this->refreshWith($rt);
                } catch (RateLimitError $e) {
                    // Refused before it was used: keep it for the next attempt.
                    $this->tokenStore->set($cur);
                    throw $e;
                }
                if ($fresh !== null) {
                    return $fresh;
                }
            }
            return $this->login();
        });
    }

    /**
     * Renew with the refresh token. A 429 means the token was not used: wait and send the SAME
     * token again (never sign in instead, which would turn renewal pressure into sign-ins).
     */
    private function refreshWith(#[\SensitiveParameter] string $refreshToken): ?Tokens
    {
        $attempt = 0;
        while (true) {
            try {
                $res = $this->http->request('POST', $this->baseUrl . '/auth/refresh', [
                    'headers' => $this->baseHeaders(),
                    'json' => ['refresh_token' => $refreshToken],
                    'http_errors' => false,
                    'allow_redirects' => false,
                    'timeout' => $this->timeout,
                ]);
            } catch (GuzzleException) {
                return null;
            }
            if ($res->getStatusCode() !== 429) {
                break;
            }
            $ra = $this->retryAfter($res);
            if ($attempt >= $this->maxRetries || ($ra !== null && $ra > self::MAX_RETRY_AFTER)) {
                throw new RateLimitError(429, 'rate_limited', 'Renewal was rate limited.', $ra, null, 'refresh', true);
            }
            $attempt++;
            ($this->sleeper)($this->backoff($attempt, $ra));
        }
        if ($res->getStatusCode() !== 200) {
            return null; // spent, expired or revoked: sign in again
        }
        $body = $this->decode($res);
        return is_array($body) && isset($body['access_token']) ? $this->saveTokens($body) : null;
    }

    private function login(): Tokens
    {
        $waited = false;
        $attempt = 0;
        while (true) {
            try {
                $res = $this->http->request('POST', $this->baseUrl . '/auth/login', [
                    'headers' => $this->baseHeaders(),
                    'json' => ['email' => $this->email, 'password' => $this->password],
                    'http_errors' => false,
                    'allow_redirects' => false,
                    'timeout' => $this->timeout,
                ]);
            } catch (GuzzleException $e) {
                if ($attempt++ < $this->maxRetries) {
                    ($this->sleeper)($this->backoff($attempt, null));
                    continue;
                }
                throw new ApiError(0, 'network_error', 'Could not reach the API.', null, 'login', $e, true);
            }
            $status = $res->getStatusCode();
            if ($status === 429 && !$waited && $this->loginRateLimitWait > 0) {
                // Sign-in is limited to 5 per minute: wait (a minute by default), once.
                $ra = $this->retryAfter($res);
                if ($ra === null || $ra <= self::MAX_RETRY_AFTER) {
                    $waited = true;
                    ($this->sleeper)(max($this->loginRateLimitWait, (float) ($ra ?? 0)));
                    continue;
                }
            }
            if ($status >= 500 && $attempt++ < $this->maxRetries) {
                ($this->sleeper)($this->backoff($attempt, $this->retryAfter($res)));
                continue;
            }
            $body = $this->decode($res);
            if ($status === 200 && is_array($body) && ($body['status'] ?? null) === 'mfa_required') {
                throw new MfaRequiredError();
            }
            if ($status === 200 && is_array($body) && !empty($body['password_expired'])) {
                throw new PasswordExpiredError();
            }
            if ($status === 200 && is_array($body) && isset($body['access_token'])) {
                return $this->saveTokens($body);
            }
            throw $this->errorFor($status, $body, $res, 'login', []);
        }
    }

    /** @param array<string,mixed> $body */
    private function saveTokens(array $body): Tokens
    {
        $expiresIn = (int) ($body['expires_in'] ?? 900);
        $tokens = new Tokens(
            (string) $body['access_token'],
            isset($body['refresh_token']) ? (string) $body['refresh_token'] : null,
            time() + max(0, $expiresIn),
        );
        // Store the new pair before the new access token is used.
        $this->tokenStore->set($tokens);
        return $tokens;
    }

    // ------------------------------------------------------------------ transport

    /**
     * @param array<string,string> $query
     * @param array<string,string> $headers
     * @param array<string,mixed> $options
     * @return array{0:int, 1:mixed}
     */
    private function send(
        string $method,
        string $url,
        array $query,
        ?array $body,
        array $headers,
        bool $retrySafe,
        string $operationId,
        array $options,
    ): array {
        $attempt = 0;
        $renewed = false;
        while (true) {
            $token = $this->accessToken();
            $h = $this->baseHeaders() + $headers + ['Authorization' => 'Bearer ' . $token];
            if ($attempt > 0 && isset($options['retryHeaders'])) {
                $h = $options['retryHeaders'] + $h;
            }
            $req = ['headers' => $h, 'http_errors' => false, 'allow_redirects' => false, 'timeout' => $this->timeout];
            if ($query !== []) {
                $req['query'] = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            }
            if ($body !== null) {
                $req['body'] = json_encode($body === [] && ($options['emptyObject'] ?? false) ? new \stdClass() : $body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                $req['headers']['Content-Type'] = 'application/json';
            }
            try {
                $res = $this->http->request($method, $url, $req);
            } catch (GuzzleException $e) {
                if ($retrySafe && $attempt < $this->maxRetries) {
                    $attempt++;
                    ($this->sleeper)($this->backoff($attempt, null));
                    continue;
                }
                throw new ApiError(0, 'network_error', 'Could not complete the request.', null, $operationId, $e, true);
            }
            $status = $res->getStatusCode();
            if ($status === 401 && !$renewed) {
                $renewed = true;
                $this->renew($token);
                continue;
            }
            $ra = $this->retryAfter($res);
            $waitable = $ra === null || $ra <= self::MAX_RETRY_AFTER;
            if ($status === 429 && $waitable && $attempt < $this->maxRetries) {
                $attempt++;
                ($this->sleeper)($this->backoff($attempt, $ra));
                continue;
            }
            if ($status >= 500 && $retrySafe && $waitable && $attempt < $this->maxRetries) {
                $attempt++;
                ($this->sleeper)($this->backoff($attempt, $this->retryAfter($res)));
                continue;
            }
            $decoded = $this->decode($res);
            if ($status >= 300) { // redirects are not followed and count as errors
                throw $this->errorFor($status, $decoded, $res, $operationId, $options);
            }
            return [$status, $decoded];
        }
    }

    /** @return array<string,string> */
    private function baseHeaders(): array
    {
        return ['Accept' => 'application/json', 'User-Agent' => $this->userAgent];
    }

    private function decode(ResponseInterface $res): mixed
    {
        $raw = (string) $res->getBody();
        if ($raw === '') {
            return null;
        }
        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    private function retryAfter(ResponseInterface $res): ?int
    {
        $v = $res->getHeaderLine('Retry-After');
        if ($v === '') {
            return null;
        }
        if (ctype_digit($v)) {
            return (int) $v;
        }
        $t = strtotime($v);
        return $t === false ? null : max(0, $t - time());
    }

    private function backoff(int $attempt, ?int $retryAfter): float
    {
        $base = min(8.0, 0.5 * (2 ** ($attempt - 1)));
        $jittered = $base / 2 + (mt_rand() / mt_getrandmax()) * ($base / 2);
        return max($jittered, (float) ($retryAfter ?? 0));
    }

    /** @param array<string,mixed> $options */
    private function errorFor(int $status, mixed $body, ResponseInterface $res, string $operationId, array $options): ApiError
    {
        [$code, $message, $requestId] = self::normaliseError($status, $body);
        // The exception text never carries the server's message; $sdkText marks text the SDK wrote.
        $sdk = false;
        if (!empty($options['redactErrors'])) {
            // Test endpoints can echo raw server-side error text: pass on the status only.
            $message = sprintf('Request failed (HTTP %d).', $status);
            $sdk = true;
        }
        if ($status === 404 && $body === null) {
            $message = 'Not found (non-JSON response). A query string over about 2,000 characters is refused this way.';
            $sdk = true;
        }
        if ($status >= 300 && $status < 400) {
            $message = 'Redirect refused: the SDK does not follow redirects. Check the base URL.';
            $sdk = true;
        }
        if ($status === 409 && !empty($options['extensionConflict'])) {
            return new ConflictError(
                $status,
                $code,
                ConflictError::EXTENSION_CONFLICT_MESSAGE,
                $requestId,
                $operationId,
                self::conflictExtensions(is_string($message) ? $message : '', $options['requestedNumbers'] ?? []),
                null,
                true,
            );
        }
        return match (true) {
            $status === 401 => new AuthError($status, $code, $message, $requestId, $operationId, null, $sdk),
            $status === 404 => new NotFoundError($status, $code, $message, $requestId, $operationId, null, $sdk),
            $status === 409 => new ConflictError($status, $code, $message, $requestId, $operationId, [], null, $sdk),
            $status === 400, $status === 422 => new ValidationError($status, $code, $message, $requestId, $operationId, null, $sdk),
            $status === 429 => new RateLimitError($status, $code, $message, $this->retryAfter($res), $requestId, $operationId, $sdk),
            default => new ApiError($status, $code, $message, $requestId, $operationId, null, $sdk),
        };
    }

    /**
     * Normalise the three error shapes: {error:{code,message,request_id}}, {error:{type,message}}
     * and the flat {statusCode,message}; also {error:"text"}.
     *
     * @return array{0:?string, 1:string, 2:?string}
     */
    public static function normaliseError(int $status, mixed $body): array
    {
        if (is_array($body)) {
            $e = $body['error'] ?? null;
            if (is_array($e)) {
                $code = $e['code'] ?? $e['type'] ?? null;
                $msg = $e['message'] ?? null;
                $rid = $e['request_id'] ?? null;
                return [
                    is_string($code) && $code !== '' ? $code : null,
                    is_string($msg) && $msg !== '' ? $msg : sprintf('HTTP %d', $status),
                    is_string($rid) && $rid !== '' ? $rid : null,
                ];
            }
            if (is_string($e) && $e !== '') {
                return [null, $e, null];
            }
            if (isset($body['message']) && is_string($body['message'])) {
                return [null, $body['message'], null];
            }
        }
        return [null, sprintf('HTTP %d', $status), null];
    }

    /**
     * The requested extensions that the server's 409 message mentions. Nothing else from the
     * message is kept.
     *
     * @param list<string> $requested
     * @return list<string>
     */
    public static function conflictExtensions(string $message, array $requested): array
    {
        preg_match_all('/[0-9*#+]+/', $message, $m);
        $out = [];
        foreach ($m[0] ?? [] as $tok) {
            if (in_array($tok, $requested, true) && !in_array($tok, $out, true)) {
                $out[] = $tok;
            }
        }
        return $out;
    }

    /**
     * Every number listed under `monitored_numbers` anywhere in a request body.
     *
     * @return list<string>
     */
    public static function requestedNumbers(mixed $body): array
    {
        $out = [];
        if (!is_array($body)) {
            return $out;
        }
        foreach ($body as $key => $value) {
            $found = ($key === 'monitored_numbers' && is_array($value))
                ? array_map(static fn ($v) => (string) $v, array_values(array_filter($value, 'is_scalar')))
                : self::requestedNumbers($value);
            foreach ($found as $n) {
                if (!in_array($n, $out, true)) {
                    $out[] = $n;
                }
            }
        }
        return $out;
    }

    /** Refuse a base URL that is not https, except on localhost. */
    private static function checkBaseUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));
        if ($scheme === 'https' && $host !== '') {
            return;
        }
        if ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return;
        }
        throw new FilterError('baseUrl must be an https:// URL (http is accepted for localhost only).');
    }

    /** @param array<string,string> $params */
    private function buildPath(string $template, array $params, string $operationId): string
    {
        return (string) preg_replace_callback('/\{(\w+)\}/', function (array $m) use ($params, $operationId): string {
            $v = $params[$m[1]] ?? null;
            if (!is_string($v) || $v === '') {
                throw new FilterError(sprintf('%s needs %s.', $operationId, $m[1]));
            }
            return rawurlencode($v);
        }, $template);
    }

    /**
     * @param array<string,mixed> $op
     * @param array<mixed> $body
     */
    private function checkBody(string $operationId, array $op, array $body): void
    {
        $allowed = $op['body'];
        if ($allowed === null || array_is_list($body) && $body !== []) {
            return;
        }
        $unknown = array_diff(array_keys($body), $allowed);
        if ($unknown !== []) {
            throw new FilterError(sprintf(
                '%s does not accept the field(s) %s. Accepted: %s.',
                $operationId,
                implode(', ', $unknown),
                implode(', ', $allowed),
            ));
        }
    }

    // ------------------------------------------------------------------ helpers for namespaces

    /** @internal wait between connector status checks (uses the injected sleeper). */
    public function pause(float $seconds): void
    {
        ($this->sleeper)($seconds);
    }

    /** @internal seconds to wait for a connector to settle. */
    public function connectorTimeout(): float
    {
        return $this->connectorTimeout;
    }

    /** Lower-case UUID v4 for Idempotency-Key. */
    public static function uuid4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
