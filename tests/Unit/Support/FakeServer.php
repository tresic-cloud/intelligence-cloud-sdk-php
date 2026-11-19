<?php

declare(strict_types=1);

namespace IntelligenceCloud\Tests\Unit\Support;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * An in-memory stand-in for the API, enough for the lifecycle workflows. Every request is
 * recorded in $log ("METHOD /path") and $requests. Responses carry a few fields that are not in
 * the published schema, so tests can check they never reach the caller.
 */
final class FakeServer
{
    public const RESELLER = '00000000-0000-4000-8000-0000000000aa';
    public const VERTICAL = '00000000-0000-4000-8000-0000000000bb';

    /** @var list<string> */
    public array $log = [];
    /** @var list<RequestInterface> */
    public array $requests = [];

    /** @var array<string,array<string,mixed>> */
    public array $companies = [];
    /** @var array<string,array<string,mixed>> */
    public array $locations = [];
    /** @var array<string,array<string,mixed>> */
    public array $groups = [];
    /** @var array<string,array<string,mixed>> */
    public array $rules = [];
    /** @var array<string,array<string,mixed>> */
    public array $users = [];
    /** @var array<string,array<string,mixed>> */
    public array $connectors = [];
    /** @var array<string,array{webhook_url:?string, connector_mappings:list<array<string,mixed>>}> */
    public array $faConfig = [];
    /** @var array<string,array{connector_mappings:list<array<string,mixed>>, group_access:list<string>}> */
    public array $ihSetup = [];
    /** @var array<string,bool> */
    public array $ihAll = [];
    /** @var array<string,list<array<string,mixed>>> user id => mappings */
    public array $userMappings = [];
    /** @var array<string,array<string,mixed>> user id => email prefs */
    public array $emailPrefs = [];
    /** @var array<string,list<array<string,mixed>>> */
    public array $domains = [];
    /** @var list<string> user ids emailed an invitation, in order */
    public array $invitations = [];
    /** @var array<string,array{webhook_url:string, enabled:bool}> location id => After Call webhook */
    public array $acWebhooks = [];
    /** @var array<string,string> connector id => last api_key sent (create or PATCH) */
    public array $connectorKeys = [];

    /** Status a new or reactivated connector settles in. */
    public string $connectorFinal = 'active';
    public ?string $connectorStateReason = null;
    /** Polls that still show `connecting` before the final status. */
    public int $connectorPolls = 1;
    /** @var array<string,int> */
    private array $pollsLeft = [];

    private int $seq = 0;
    private int $tokenSeq = 0;
    public string $role = 'reseller_admin';

    public function handler(): callable
    {
        return function (RequestInterface $req, array $options): PromiseInterface {
            return Create::promiseFor($this->handle($req));
        };
    }

    public function id(): string
    {
        $this->seq++;
        return sprintf('00000000-0000-4000-8000-%012d', $this->seq);
    }

    /** @return list<string> log lines without auth calls */
    public function calls(): array
    {
        return array_values(array_filter($this->log, static fn ($l) => !str_contains($l, '/auth/') && $l !== 'GET /me'));
    }

    /** @return list<string> */
    public function writes(): array
    {
        return array_values(array_filter($this->calls(), static fn ($l) => !str_starts_with($l, 'GET ')));
    }

    private function handle(RequestInterface $req): Response
    {
        $this->requests[] = $req;
        $method = $req->getMethod();
        $path = (string) preg_replace('#^/api/v1#', '', $req->getUri()->getPath());
        parse_str($req->getUri()->getQuery(), $q);
        $raw = (string) $req->getBody();
        $body = $raw === '' ? [] : json_decode($raw, true);
        $this->log[] = $method . ' ' . $path;

        if ($path === '/auth/login') {
            $this->tokenSeq++;
            return $this->json(200, ['access_token' => 'at' . $this->tokenSeq, 'refresh_token' => 'rt' . $this->tokenSeq, 'token_type' => 'Bearer', 'expires_in' => 899, 'user' => ['id' => 'x']]);
        }
        if ($path === '/me') {
            return $this->json(200, ['data' => ['id' => 'me', 'role' => $this->role, 'reseller_id' => self::RESELLER, 'unexpected_flag' => true]]);
        }
        if ($path === '/verticals') {
            return $this->json(200, ['data' => [['id' => self::VERTICAL, 'key' => 'csps-msps', 'name' => 'CSPs & MSPs']]]);
        }

        $r = $this->route($method, $path, $q, is_array($body) ? $body : [], $req);
        if ($r === null) {
            return $this->json(404, ['error' => ['code' => 'NOT_FOUND', 'message' => 'no fake route for ' . $method . ' ' . $path]]);
        }
        return $this->json($r[0], $r[1]);
    }

    /**
     * @param array<string,mixed> $q
     * @param array<string,mixed> $b
     * @return array{0:int, 1:mixed}|null
     */
    private function route(string $m, string $path, array $q, array $b, RequestInterface $req): ?array
    {
        $U = '([0-9a-f-]{36})';
        // ------------------------------------------------ companies
        if (preg_match("#^/resellers/$U/companies$#", $path)) {
            if ($m === 'GET') {
                $rows = array_values(array_filter($this->companies, fn ($c) => !isset($q['search']) || stripos($c['company_name'], (string) $q['search']) === 0));
                return [200, ['data' => array_map(fn ($c) => ['type' => 'company', 'attributes' => $c], $rows), 'has_more' => false, 'total_count' => count($rows)]];
            }
            foreach ($this->companies as $c) {
                if ($c['primary_admin_contact_email'] === $b['primary_admin_contact_email']) {
                    return [409, ['error' => ['code' => 'CONFLICT', 'message' => 'The provided contact email is already in use on the platform']]];
                }
            }
            $id = $this->id();
            $this->companies[$id] = ['id' => $id, 'company_name' => $b['company_name'], 'primary_admin_contact_email' => $b['primary_admin_contact_email'],
                'enabled_products' => $b['enabled_products'] ?? [], 'is_active' => true, 'mfa_enforced' => true];
            return [201, ['data' => ['type' => 'company', 'attributes' => $this->companies[$id]], 'invited_user' => ['id' => $this->id(), 'status' => 'invited']]];
        }
        if (preg_match("#^/companies/$U/(deactivate|reactivate)$#", $path, $mm)) {
            $this->companies[$mm[1]]['is_active'] = $mm[2] === 'reactivate';
            return [200, ['data' => ['type' => 'company', 'attributes' => $this->companies[$mm[1]]]]];
        }
        if (preg_match("#^/companies/$U$#", $path, $mm)) {
            $c = &$this->companies[$mm[1]];
            if ($m === 'PATCH') {
                foreach ($b as $k => $v) {
                    $c[$k] = $v;
                }
            }
            return [200, ['data' => ['type' => 'company', 'attributes' => $c]]];
        }
        if (preg_match("#^/companies/$U/domains$#", $path, $mm)) {
            $d = ['id' => $this->id(), 'domain' => $b['domain'], 'status' => 'pending',
                'verification' => ['type' => 'TXT', 'name' => '_ic-verify.' . $b['domain'], 'value' => 'abc123']];
            return [201, ['data' => $d]];
        }
        // ------------------------------------------------ locations
        if (preg_match("#^/companies/$U/locations$#", $path, $mm)) {
            if ($m === 'GET') {
                $rows = array_values(array_filter($this->locations, fn ($l) => $l['company_id'] === $mm[1]
                    && (!isset($q['search']) || stripos($l['location_name'], (string) $q['search']) !== false)
                    && (!isset($q['is_active']) || ($q['is_active'] === 'true') === $l['is_active'])));
                return [200, ['data' => array_map(fn ($l) => ['type' => 'location', 'attributes' => $l], $rows), 'pagination' => ['has_more' => false]]];
            }
            $id = $this->id();
            $this->locations[$id] = ['id' => $id, 'company_id' => $mm[1], 'location_name' => $b['location_name'], 'is_active' => true,
                'enabled_products' => $b['enabled_products'] ?? [], 'vertical_id' => $b['vertical_id'], 'unexpected_flag' => false];
            $this->faConfig[$id] = ['webhook_url' => null, 'connector_mappings' => []];
            return [201, ['data' => ['type' => 'location', 'attributes' => $this->locations[$id]]]];
        }
        if (preg_match("#^/companies/$U/locations/$U$#", $path, $mm)) {
            $l = &$this->locations[$mm[2]];
            if ($m === 'PATCH') {
                foreach ($b as $k => $v) {
                    $l[$k] = $v;
                }
            }
            return [200, ['data' => ['type' => 'location', 'attributes' => $l]]];
        }
        // ------------------------------------------------ groups
        if (preg_match("#^/locations/$U/user-groups$#", $path, $mm)) {
            if ($m === 'GET') {
                $rows = array_values(array_filter($this->groups, fn ($g) => $g['location_id'] === $mm[1]));
                return [200, ['data' => array_map(fn ($g) => ['type' => 'user_group', 'attributes' => array_diff_key($g, ['members' => 1])], $rows), 'pagination' => ['has_more' => false]]];
            }
            $id = $this->id();
            $this->groups[$id] = ['id' => $id, 'location_id' => $mm[1], 'group_name' => $b['group_name'], 'user_count' => 0, 'members' => []];
            return [201, ['data' => ['type' => 'user_group', 'attributes' => $this->groups[$id]]]];
        }
        if (preg_match("#^/locations/$U/user-groups/$U$#", $path, $mm)) {
            $g = &$this->groups[$mm[2]];
            if ($m === 'PATCH' && isset($b['member_ids'])) {
                $g['members'] = array_map(fn ($id) => ['id' => $id], $b['member_ids']);
            }
            return [200, ['data' => ['type' => 'user_group', 'attributes' => $g]]];
        }
        if (preg_match("#^/locations/$U/users/$U/group-membership$#", $path, $mm)) {
            if ($m === 'PUT') {
                foreach ($this->groups as &$g) {
                    if ($g['location_id'] !== $mm[1]) {
                        continue;
                    }
                    $ids = array_column($g['members'], 'id');
                    $ids = array_values(array_diff($ids, [$mm[2]]));
                    if (in_array($g['id'], $b['group_ids'], true)) {
                        $ids[] = $mm[2];
                    }
                    $g['members'] = array_map(fn ($id) => ['id' => $id], $ids);
                }
                unset($g);
            }
            $out = [];
            foreach ($this->groups as $g) {
                if ($g['location_id'] === $mm[1]) {
                    $out[] = ['id' => $g['id'], 'name' => $g['group_name'], 'is_member' => in_array($mm[2], array_column($g['members'], 'id'), true), 'product_keys' => []];
                }
            }
            return [200, ['data' => $out]];
        }
        // ------------------------------------------------ rules
        if (preg_match("#^/locations/$U/first-alert/rules$#", $path, $mm)) {
            if ($m === 'GET') {
                $rows = array_values(array_filter($this->rules, fn ($r) => $r['location_id'] === $mm[1]));
                return [200, ['data' => array_map(fn ($r) => ['type' => 'first_alert_rule', 'attributes' => array_diff_key($r, ['triggers' => 1])], $rows), 'pagination' => ['has_more' => false, 'next_cursor' => null]]];
            }
            $id = $this->id();
            $this->rules[$id] = ['id' => $id, 'location_id' => $mm[1], 'name' => $b['name'], 'priority' => $b['priority'], 'enabled' => $b['enabled'] ?? false,
                'match_logic' => $b['match_logic'] ?? 'any', 'triggers' => $b['triggers'], 'groups' => array_map(fn ($g) => ['id' => $g], $b['group_ids'] ?? [])];
            return [201, ['data' => ['type' => 'first_alert_rule', 'attributes' => $this->rules[$id]]]];
        }
        if (preg_match("#^/locations/$U/first-alert/rules/$U$#", $path, $mm)) {
            $r = &$this->rules[$mm[2]];
            if ($m === 'PATCH') {
                foreach ($b as $k => $v) {
                    if ($k === 'group_ids') {
                        $r['groups'] = array_map(fn ($g) => ['id' => $g], $v);
                    } else {
                        $r[$k] = $v;
                    }
                }
            }
            return [200, ['data' => ['type' => 'first_alert_rule', 'attributes' => $r]]];
        }
        if (preg_match("#^/locations/$U/first-alert/config$#", $path, $mm)) {
            if ($m === 'PUT') {
                $this->faConfig[$mm[1]] = ['webhook_url' => $b['webhook_url'] ?? null, 'connector_mappings' => $b['connector_mappings']];
            }
            $cfg = $this->faConfig[$mm[1]] ?? ['webhook_url' => null, 'connector_mappings' => []];
            $cfg['unexpected_flag'] = false;
            $cfg['connector_mappings'] = array_map(fn ($x) => $x + ['id' => 'm', 'connector_name' => 'PBX', 'connector_type' => 'netsapiens'], $cfg['connector_mappings']);
            return [200, ['data' => $cfg]];
        }
        // ------------------------------------------------ insights hub
        if (preg_match("#^/locations/$U/insights-hub/setup$#", $path, $mm)) {
            $s = $this->ihSetup[$mm[1]] ?? ['connector_mappings' => [], 'group_access' => []];
            if ($m !== 'GET') {
                foreach (['connector_mappings', 'group_access'] as $k) {
                    if (array_key_exists($k, $b)) {
                        $s[$k] = $b[$k];
                    }
                }
                $this->ihSetup[$mm[1]] = $s;
            }
            return [$m === 'POST' ? 201 : 200, ['data' => ['type' => 'insights_hub_setup', 'attributes' => $s + ['available_connectors' => [], 'groups' => []]]]];
        }
        if (preg_match("#^/locations/$U/insights-hub/config$#", $path, $mm)) {
            if ($m === 'PATCH') {
                $this->ihAll[$mm[1]] = (bool) $b['monitor_all_numbers'];
            }
            return [200, ['data' => ['type' => 'insights_hub_config', 'attributes' => ['monitor_all_numbers' => $this->ihAll[$mm[1]] ?? false]]]];
        }
        // ------------------------------------------------ connectors
        if (preg_match("#^/locations/$U/connectors/test$#", $path)) {
            return [200, ['data' => ['success' => true, 'error' => null]]];
        }
        if (preg_match("#^/locations/$U/connectors$#", $path, $mm)) {
            if ($m === 'GET') {
                $rows = array_values(array_filter($this->connectors, fn ($c) => $c['location_id'] === $mm[1]));
                return [200, ['data' => $rows, 'pagination' => ['has_more' => false]]];
            }
            $id = $this->id();
            $this->connectors[$id] = ['id' => $id, 'location_id' => $mm[1], 'name' => $b['name'], 'type' => 'netsapiens', 'status' => 'connecting',
                'config' => ['api_url' => $b['config']['api_url'], 'domain' => $b['config']['domain'], 'internal_ref' => 'redacted'], 'subscription_id' => null];
            $this->pollsLeft[$id] = $this->connectorPolls;
            $this->connectorKeys[$id] = $b['config']['api_key'];
            return [202, ['data' => $this->connectors[$id]]];
        }
        if (preg_match("#^/locations/$U/connectors/$U(/(deactivate|reactivate))?$#", $path, $mm)) {
            $id = $mm[2];
            $c = &$this->connectors[$id];
            $action = $mm[4] ?? '';
            if ($m === 'PATCH') {
                if (isset($b['name'])) {
                    $c['name'] = $b['name'];
                }
                if (isset($b['config'])) {
                    $c['config']['api_url'] = $b['config']['api_url'];
                    $c['config']['domain'] = $b['config']['domain'];
                    $this->connectorKeys[$id] = $b['config']['api_key'];
                }
                return [200, ['data' => $c]];
            }
            if ($action === 'deactivate') {
                if ($c['status'] === 'inactive') {
                    return [409, ['error' => ['code' => 'CONFLICT', 'message' => 'Connector is already inactive']]];
                }
                $c['status'] = 'deactivating';
                $this->pollsLeft[$id] = 0;
                return [202, ['data' => $c]];
            }
            if ($action === 'reactivate') {
                $c['status'] = 'connecting';
                $this->pollsLeft[$id] = $this->connectorPolls;
                return [202, ['data' => $c]];
            }
            if ($c['status'] === 'deactivating') {
                $c['status'] = 'inactive';
            } elseif ($c['status'] === 'connecting') {
                if (($this->pollsLeft[$id] ?? 0) > 0) {
                    $this->pollsLeft[$id]--;
                } else {
                    $c['status'] = $this->connectorFinal;
                    if ($this->connectorStateReason !== null) {
                        $c['state_reason'] = $this->connectorStateReason;
                        $c['status_message'] = 'The key was refused.';
                    }
                }
            }
            return [200, ['data' => $c]];
        }
        // ------------------------------------------------ After Call webhook
        if (preg_match("#^/locations/$U/after-call/automation$#", $path, $mm)) {
            if ($m === 'PUT') {
                $this->acWebhooks[$mm[1]] = ['webhook_url' => $b['webhook_url'], 'enabled' => (bool) $b['enabled']];
            }
            return [200, ['data' => isset($this->acWebhooks[$mm[1]]) ? ['id' => 'w'] + $this->acWebhooks[$mm[1]] : null]];
        }
        // ------------------------------------------------ users
        if (preg_match("#^/companies/$U/users$#", $path, $mm)) {
            if ($m === 'GET') {
                $rows = array_values(array_filter($this->users, fn ($u) => $u['company_id'] === $mm[1]
                    && (!isset($q['location_id']) || array_intersect(explode(',', (string) $q['location_id']), $u['locations']) !== [])
                    && (!isset($q['search']) || stripos($u['email'], (string) $q['search']) !== false)));
                return [200, ['data' => array_map(fn ($u) => ['type' => 'company_user', 'attributes' => $this->userAttrs($u)], $rows), 'pagination' => ['has_more' => false]]];
            }
            if ($req->getHeaderLine('Idempotency-Key') === '') {
                return [400, ['error' => 'Idempotency-Key header is required']];
            }
            foreach ($this->users as $u) {
                if (strcasecmp($u['email'], $b['email']) === 0) {
                    return [409, ['error' => ['code' => 'CONFLICT', 'message' => 'This email address is already in use']]];
                }
            }
            $id = $this->id();
            $this->users[$id] = ['id' => $id, 'company_id' => $mm[1], 'email' => $b['email'], 'first_name' => $b['first_name'], 'last_name' => $b['last_name'],
                'role' => $b['role'], 'status' => 'invited', 'locations' => $b['location_ids'] ?? [], 'products' => []];
            if (!empty($b['send_invitation'])) {
                $this->invitations[] = $id;
            }
            return [201, ['data' => ['type' => 'company_user', 'attributes' => $this->userAttrs($this->users[$id]) + ['external_id' => 'pending:' . $id]], 'meta' => ['invitation_link' => 'https://example.com/secret-link']]];
        }
        if ($m === 'POST' && preg_match("#^/companies/$U/users/$U/resend-invitation$#", $path, $mm)) {
            $u = $this->users[$mm[2]];
            if ($u['status'] !== 'invited') {
                return [409, ['error' => ['code' => 'CONFLICT', 'message' => 'User does not have a pending invitation']]];
            }
            $this->invitations[] = $mm[2];
            return [202, ['data' => ['type' => 'invitation_resend', 'attributes' => ['user_id' => $mm[2], 'email' => $u['email']]]]];
        }
        if (preg_match("#^/companies/$U/users/$U(/(deactivate|reactivate))?$#", $path, $mm)) {
            $u = &$this->users[$mm[2]];
            $action = $mm[4] ?? '';
            if ($action === 'deactivate') {
                if ($u['status'] === 'deactivated') {
                    return [409, ['error' => ['code' => 'CONFLICT', 'message' => 'User is already deactivated']]];
                }
                $u['status'] = 'deactivated';
            } elseif ($action === 'reactivate') {
                $u['status'] = 'active';
            } elseif ($m === 'PATCH') {
                if (isset($b['location_ids'])) {
                    $u['locations'] = $b['location_ids'];
                }
                foreach (['first_name', 'last_name', 'role', 'department'] as $k) {
                    if (isset($b[$k])) {
                        $u[$k] = $b[$k];
                    }
                }
            }
            return [200, ['data' => ['type' => 'company_user', 'attributes' => $this->userAttrs($u)]]];
        }
        if (preg_match("#^/companies/$U/users/$U/connector-mappings$#", $path, $mm)) {
            if ($m === 'PUT') {
                foreach ($b['mappings'] as $map) {
                    $c = $this->connectors[$map['connector_id']] ?? null;
                    if ($c === null || $c['status'] !== 'active') {
                        return [422, ['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'connector not found or inactive']]];
                    }
                }
                $this->userMappings[$mm[2]] = $b['mappings'];
            }
            return [200, ['data' => array_map(fn ($x) => $x + ['connector_name' => 'PBX', 'connector_type' => 'netsapiens'], $this->userMappings[$mm[2]] ?? [])]];
        }
        if (preg_match("#^/locations/$U/users/$U/products/([a-z-]+)/(enable|disable)$#", $path, $mm)) {
            $u = &$this->users[$mm[2]];
            $u['products'] = array_values(array_diff($u['products'], [$mm[3]]));
            if ($mm[4] === 'enable') {
                $u['products'][] = $mm[3];
            }
            return [200, ['data' => ['product_key' => $mm[3], 'enabled' => $mm[4] === 'enable']]];
        }
        if (preg_match("#^/locations/$U/users/$U/first-alert/notification-preferences$#", $path, $mm)) {
            $alerts = [];
            foreach ($this->rules as $r) {
                if ($r['location_id'] !== $mm[1]) {
                    continue;
                }
                foreach ($r['groups'] as $g) {
                    if (in_array($mm[2], array_column($this->groups[$g['id']]['members'] ?? [], 'id'), true)) {
                        $alerts[] = ['alertId' => $r['id'], 'alertName' => $r['name'], 'emailEnabled' => false];
                    }
                }
            }
            return [200, ['emailNotifications' => ['masterEnabled' => false, 'alerts' => $alerts], 'digest' => ['enabled' => false], 'webhook' => ['url' => null]]];
        }
        if (preg_match("#^/locations/$U/users/$U/first-alert/email-preferences$#", $path, $mm)) {
            $this->emailPrefs[$mm[2]] = $b;
            return [200, ['emailNotifications' => $b, 'digest' => ['enabled' => false]]];
        }
        if (preg_match("#^/locations/$U/users(/$U)?$#", $path, $mm)) {
            $rows = [];
            foreach ($this->users as $u) {
                if (in_array($mm[1], $u['locations'], true)) {
                    $groups = [];
                    foreach ($this->groups as $g) {
                        if ($g['location_id'] === $mm[1] && in_array($u['id'], array_column($g['members'], 'id'), true)) {
                            $groups[] = $g['group_name'];
                        }
                    }
                    $rows[$u['id']] = ['id' => $u['id'], 'email' => $u['email'], 'role' => $u['role'], 'status' => $u['status'], 'location_id' => $mm[1],
                        'groups' => $groups, 'enabled_products' => $u['products']];
                }
            }
            if (isset($mm[3])) {
                return isset($rows[$mm[3]]) ? [200, ['data' => ['type' => 'location_user', 'attributes' => $rows[$mm[3]]]]] : null;
            }
            return [200, ['data' => array_map(fn ($r) => ['type' => 'location_user', 'attributes' => $r], array_values($rows)), 'pagination' => ['has_more' => false]]];
        }
        return null;
    }

    /** @param array<string,mixed> $u @return array<string,mixed> */
    private function userAttrs(array $u): array
    {
        return ['id' => $u['id'], 'company_id' => $u['company_id'], 'email' => $u['email'], 'first_name' => $u['first_name'], 'last_name' => $u['last_name'],
            'role' => $u['role'], 'status' => $u['status'], 'department' => $u['department'] ?? '', 'locations' => array_map(fn ($l) => ['location_id' => $l], $u['locations'])];
    }

    private function json(int $status, mixed $body): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
    }
}
