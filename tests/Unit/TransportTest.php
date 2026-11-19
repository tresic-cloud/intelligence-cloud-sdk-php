<?php

declare(strict_types=1);

namespace IntelligenceCloud\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use IntelligenceCloud\Client;
use IntelligenceCloud\Exception\ApiError;
use IntelligenceCloud\Exception\ConflictError;
use IntelligenceCloud\Exception\NotFoundError;
use IntelligenceCloud\Exception\RateLimitError;
use IntelligenceCloud\Exception\ValidationError;
use IntelligenceCloud\Tests\Unit\Support\TestCase;

/** Retries, backoff, error normalisation and response projection. */
final class TransportTest extends TestCase
{
    private const LID = '0193b6c2-6b29-7a51-b0e4-1c8f3d5e7a02';
    private const CID = '4b7e9d2c-1a3f-4e8b-9c6d-2f5a8e1b3c70';

    // ------------------------------------------------------------ retries

    public function testRetriesA429HonouringRetryAfter(): void
    {
        $ic = $this->scripted([
            self::login(),
            self::json(429, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'x']], ['Retry-After' => '7']),
            self::json(200, ['data' => []]),
        ]);
        $ic->products->listProducts();
        self::assertSame([7.0], $this->sleeps);
        self::assertCount(3, $this->history);
    }

    public function testGivesUpAfterThreeRetriesWithRateLimitError(): void
    {
        $r = fn () => self::json(429, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'x']], ['Retry-After' => '1']);
        $ic = $this->scripted([self::login(), $r(), $r(), $r(), $r()]);
        try {
            $ic->products->listProducts();
            self::fail('expected RateLimitError');
        } catch (RateLimitError $e) {
            self::assertSame(429, $e->status);
            self::assertSame(1, $e->retryAfter);
        }
        self::assertCount(3, $this->sleeps);
    }

    public function testRetries5xxOnReadsWithJitteredBackoff(): void
    {
        $ic = $this->scripted([self::login(), new Response(502), new Response(503), self::json(200, ['data' => []])]);
        $ic->products->listProducts();
        self::assertCount(2, $this->sleeps);
        self::assertGreaterThanOrEqual(0.25, $this->sleeps[0]);
        self::assertLessThanOrEqual(0.5, $this->sleeps[0]);
        self::assertGreaterThanOrEqual(0.5, $this->sleeps[1]);
        self::assertLessThanOrEqual(1.0, $this->sleeps[1]);
    }

    public function testDoesNotRetry5xxOnNonIdempotentCreate(): void
    {
        $ic = $this->scripted([self::login(), new Response(500), self::json(201, [])]);
        try {
            $ic->locations->createLocation('0193b6c2-5a11-7c3e-8f20-6d4b9e1c2a77', ['location_name' => 'X']);
            self::fail('expected ApiError');
        } catch (ApiError $e) {
            self::assertSame(500, $e->status);
        }
        self::assertSame([], $this->sleeps);
    }

    // ------------------------------------------------------------ errors

    public function testErrorShapesAreNormalised(): void
    {
        self::assertSame(['VALIDATION_ERROR', 'bad', 'rid'], Client::normaliseError(422, ['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'bad', 'request_id' => 'rid']]));
        self::assertSame(['invalid_parameter', 'bad status', null], Client::normaliseError(400, ['error' => ['type' => 'invalid_parameter', 'message' => 'bad status']]));
        self::assertSame([null, 'Unauthorized', null], Client::normaliseError(401, ['statusCode' => 401, 'message' => 'Unauthorized']));
        self::assertSame([null, 'Idempotency-Key header is required', null], Client::normaliseError(400, ['error' => 'Idempotency-Key header is required']));
        self::assertSame([null, 'HTTP 502', null], Client::normaliseError(502, null));
    }

    public function testStatusMapsToErrorClass(): void
    {
        $cases = [
            [404, NotFoundError::class],
            [409, ConflictError::class],
            [400, ValidationError::class],
            [422, ValidationError::class],
            [403, ApiError::class],
        ];
        foreach ($cases as [$status, $class]) {
            $ic = $this->scripted([self::login(), self::json($status, ['error' => ['code' => 'X', 'message' => 'm', 'request_id' => 'r1']])]);
            try {
                $ic->companies->getCompany('0193b6c2-5a11-7c3e-8f20-6d4b9e1c2a77');
                self::fail('expected ' . $class);
            } catch (ApiError $e) {
                self::assertInstanceOf($class, $e);
                self::assertSame($status, $e->status);
                self::assertSame('r1', $e->requestId);
                self::assertSame('getCompany', $e->operationId);
            }
        }
    }

    public function testNotFoundSaysItCoversOutOfScopeRecords(): void
    {
        $ic = $this->scripted([self::login(), self::json(404, ['error' => ['code' => 'NOT_FOUND', 'message' => 'Call not found']])]);
        try {
            $ic->calls->getCall('0193b6d0-1e2f-7a3b-8c4d-5e6f7a8b9c01');
            self::fail();
        } catch (NotFoundError $e) {
            self::assertSame('HTTP 404 NOT_FOUND: Not found, or outside what this login can see.', $e->getMessage());
            self::assertSame('Call not found', $e->serverMessage);
        }
    }

    public function testExtensionConflictTextIsReplaced(): void
    {
        $text = 'Conflict: 2002 and 2008 are taken (example detail the SDK must not pass on)';
        foreach (['setUserNumberMappings', 'updateUserAfterCallSettings'] as $method) {
            $ic = $this->scripted([self::login(), self::json(409, ['error' => ['code' => 'CONFLICT', 'message' => $text]])]);
            try {
                if ($method === 'setUserNumberMappings') {
                    $ic->users->setUserNumberMappings('0193b6c2-5a11-7c3e-8f20-6d4b9e1c2a77', '0193b6c2-7c44-7d12-a3f9-8e2b6c1d4f55', ['mappings' => [['connector_id' => self::CID, 'monitored_numbers' => ['2001', '2002', '2008']]]]);
                } else {
                    $ic->afterCallSettings->updateUserAfterCallSettings(self::LID, '0193b6c2-7c44-7d12-a3f9-8e2b6c1d4f55', ['connector_mappings' => [['connector_id' => self::CID, 'monitored_numbers' => ['2001', '2002', '2008']]]]);
                }
                self::fail('expected ConflictError');
            } catch (ConflictError $e) {
                self::assertSame('HTTP 409 CONFLICT: Extension is already monitored at another location.', $e->getMessage());
                self::assertNull($e->serverMessage);
                self::assertSame(['2002', '2008'], $e->extensions);
                self::assertStringNotContainsString('example detail', $e->getMessage());
            }
        }
        // no numbers requested: the text is still replaced, and no extensions are reported
        $ic = $this->scripted([self::login(), self::json(409, ['error' => ['code' => 'CONFLICT', 'message' => $text]])]);
        try {
            $ic->afterCallSettings->updateUserAfterCallSettings(self::LID, '0193b6c2-7c44-7d12-a3f9-8e2b6c1d4f55', ['connector_mappings' => []]);
            self::fail('expected ConflictError');
        } catch (ConflictError $e) {
            self::assertSame('HTTP 409 CONFLICT: Extension is already monitored at another location.', $e->getMessage());
            self::assertSame([], $e->extensions);
        }
    }

    public function testTestEndpointsNeverPassOnServerErrorText(): void
    {
        $ic = $this->scripted([
            self::login(),
            self::json(200, ['data' => ['success' => false, 'error' => 'upstream detail: 192.0.2.10 refused']]),
            self::json(422, ['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'upstream detail: 192.0.2.10 refused']]),
            self::json(200, ['data' => ['success' => false, 'http_status' => 0, 'response_time_ms' => 3, 'error' => 'upstream detail: 192.0.2.10 refused']]),
            self::json(200, ['data' => ['success' => false, 'status_code' => 500, 'message' => 'raw server text']]),
        ]);
        $r = $ic->connectors->testConnector(self::LID, ['type' => 'netsapiens', 'config' => ['api_url' => 'https://pbx.example.com/ns-api/v2', 'domain' => 'd', 'api_key' => 'k']]);
        self::assertSame(['data' => ['success' => false]], $r);
        try {
            $ic->firstAlert->testFirstAlertWebhook(self::LID, ['url' => 'https://hooks.example.com/x']);
            self::fail();
        } catch (ValidationError $e) {
            self::assertSame('HTTP 422 VALIDATION_ERROR: Request failed (HTTP 422).', $e->getMessage());
            self::assertNull($e->serverMessage);
        }
        self::assertSame(['data' => ['success' => false, 'http_status' => 0, 'response_time_ms' => 3]], $ic->webhooks->testAfterCallWebhook(self::LID));
        self::assertSame(['data' => ['success' => false, 'status_code' => 500]], $ic->firstAlert->testFirstAlertWebhook(self::LID, ['url' => 'https://hooks.example.com/x']));
    }

    public function testNonJson404FromLongQueryString(): void
    {
        $ic = $this->scripted([self::login(), new Response(404, ['Content-Type' => 'text/html'], '<html>not found</html>')]);
        $this->expectException(NotFoundError::class);
        $this->expectExceptionMessageMatches('/2,000 characters/');
        $ic->products->listProducts();
    }

    // ------------------------------------------------------------ projection

    public function testUnlistedConnectorFieldsAreStripped(): void
    {
        $ic = $this->scripted([self::login(), self::json(200, ['data' => [
            'id' => self::CID, 'name' => 'PBX', 'status' => 'error', 'state_reason' => 'invalid_permissions',
            'config' => ['api_url' => 'https://pbx.example.com/ns-api/v2', 'domain' => 'd', 'internal_ref' => 'redacted'],
            'subscription_id' => null,
        ]])]);
        $c = $ic->connectors->getConnector(self::LID, self::CID)['data'];
        self::assertSame(['api_url' => 'https://pbx.example.com/ns-api/v2', 'domain' => 'd'], $c['config']);
        self::assertArrayNotHasKey('subscription_id', $c);
        self::assertSame('invalid_permissions', $c['state_reason']);
    }

    public function testInvitationLinkAndFlagsAreStripped(): void
    {
        $ic = $this->scripted([
            self::login(),
            self::json(201, ['data' => ['type' => 'company_user', 'attributes' => ['id' => 'u1', 'email' => 'a@example.com', 'external_id' => 'pending:u1']], 'meta' => ['invitation_link' => 'https://example.com/one-time']]),
            self::json(200, ['data' => ['id' => 'me', 'role' => 'frontline_user', 'unexpected_flag' => true, 'unexpected_flag_b' => false]]),
        ]);
        $u = $ic->users->createCompanyUser('0193b6c2-5a11-7c3e-8f20-6d4b9e1c2a77', ['email' => 'a@example.com', 'first_name' => 'A', 'last_name' => 'B', 'role' => 'customer_admin']);
        self::assertArrayNotHasKey('meta', $u);
        self::assertArrayNotHasKey('external_id', $u['data']['attributes']);
        self::assertStringNotContainsString('one-time', json_encode($u));
        self::assertSame(['id' => 'me', 'role' => 'frontline_user'], $ic->intelligence->whoami());
    }

    public function testCallDetailUndocumentedFieldsAreDropped(): void
    {
        $ic = $this->scripted([self::login(), self::json(200, ['data' => [
            'id' => 'c1', 'summary' => 's', 'unexpected_field' => 0.9, 'subject' => null,
            'analysis' => ['sentiment' => ['overall' => 'negative', 'unexpected_field' => 'x'], 'topics' => [['name' => 'Billing', 'unexpected_field' => 'x']]],
            'vcon' => ['unexpected_id' => 'x', 'recording_url' => 'https://signed.example/x'],
            'tasks' => [['id' => 't1', 'title' => 'Call back', 'assignee' => 'free text', 'assignee_user' => null]],
        ]])]);
        $c = $ic->calls->getCall('0193b6d0-1e2f-7a3b-8c4d-5e6f7a8b9c01')['data'];
        self::assertSame(['id', 'summary', 'analysis', 'vcon', 'tasks'], array_keys($c));
        self::assertSame(['recording_url' => 'https://signed.example/x'], $c['vcon'], 'only the recording fields of vcon survive');
        self::assertSame(['overall' => 'negative'], $c['analysis']['sentiment']);
        self::assertSame([['name' => 'Billing']], $c['analysis']['topics']);
        self::assertSame(['id' => 't1', 'title' => 'Call back', 'assignee_user' => null], $c['tasks'][0]);
    }
}
