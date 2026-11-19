<?php

declare(strict_types=1);

namespace IntelligenceCloud\Lifecycle;

use IntelligenceCloud\Api\Mappings;
use IntelligenceCloud\Client;
use IntelligenceCloud\Exception\ApiError;
use IntelligenceCloud\Exception\ConflictError;
use IntelligenceCloud\Exception\ConnectorError;
use IntelligenceCloud\Exception\ExtensionMappingError;
use IntelligenceCloud\Exception\FilterError;
use IntelligenceCloud\Exception\ValidationError;
use IntelligenceCloud\Internal\Query;

/**
 * Base Intelligence Seat lifecycle (admin credentials).
 *
 * A Seat customer is one company with one location, the "Upset Customer" and "Customer Threat"
 * alerts routed to a "Manager Alerts" group, and an "Insights Hub Access" group.
 *
 * - End users (frontline) have After Call on their own line.
 * - Managers have After Call on all lines, First Alert and Insights Hub. A manager's own phone
 *   line is never captured on the Seat: their mapping is exactly the end users' lines.
 * - Integration users (company-user `department` "integration", see addIntegrationUser) read
 *   data for another system. They are never counted as end users or managers; like managers they
 *   are kept on all lines. A user who is not a manager and holds more than one line is treated
 *   the same way: never as a seat.
 *
 * Every workflow is safe to run again: it finds existing objects by name or email and carries on.
 * Teams and departments are locations; an extension belongs to exactly one location.
 */
final class Workflows
{
    public function __construct(private readonly Client $c)
    {
    }

    // ================================================================ provisioning

    /**
     * Build a Base Intelligence Seat customer, or finish one a previous run started.
     *
     * Company (all three products) → MFA off (`mfaOff`) → location → groups → rules (created
     * disabled, routed to Manager Alerts) → connector (optional) → Insights Hub group access and
     * all-numbers coverage → SSO domain claim (optional). The rules stay off: addManager switches
     * them on once a manager is in place (or call activateSeatAlerts). A re-run never switches a
     * rule off.
     *
     * A connector that ends in `error` is recorded on the result, not thrown. An inactive company
     * or location found by name is an error unless `reactivate` is set.
     */
    public function provisionSeatCustomer(SeatCustomerSpec $spec): SeatCustomer
    {
        $c = $this->c;
        $phrases = array_values(array_filter(array_map(static fn ($p) => trim((string) $p), $spec->threatPhrases ?? []), static fn ($p) => $p !== ''));
        $created = [];
        $existing = [];
        $resellerId = $spec->resellerId ?? (Client::unwrap($c->account->getMe())['reseller_id'] ?? null);
        if (!is_string($resellerId) || $resellerId === '') {
            throw new FilterError('resellerId is required (this login has none).');
        }

        // 1. company: the explicit companyId, or a same-named company whose contact email matches
        $company = $this->findSeatCompany($resellerId, $spec);
        if ($company === null && $phrases === []) {
            throw new FilterError(SeatDefaults::THREAT_PHRASES_MISSING);
        }
        if ($company === null) {
            $body = [
                'company_name' => $spec->companyName,
                'primary_admin_contact_name' => $spec->adminName,
                'primary_admin_contact_email' => $spec->adminEmail,
                'primary_admin_contact_phone' => $spec->adminPhone,
                'enabled_products' => SeatDefaults::PRODUCTS,
            ];
            if ($spec->companyCode !== null) {
                $body['company_code'] = $spec->companyCode;
            }
            $company = Client::unwrap($c->companies->createCompany($resellerId, $body));
            $created[] = 'company';
        } else {
            $existing[] = 'company';
            if (empty($company['is_active'])) {
                if (!$spec->reactivate) {
                    throw new FilterError(sprintf('Company "%s" exists but is inactive (set reactivate: true to reactivate it).', $spec->companyName));
                }
                $c->companies->reactivateCompany((string) $company['id']);
            }
            if (array_diff(SeatDefaults::PRODUCTS, $company['enabled_products'] ?? []) !== []) {
                $c->companies->addProducts((string) $company['id'], SeatDefaults::PRODUCTS);
            }
        }
        $companyId = (string) $company['id'];

        // 2. MFA off for an integration-user company, then verify
        if ($spec->mfaOff) {
            if (!empty(Client::unwrap($c->companies->getCompany($companyId))['mfa_enforced'])) {
                $c->companies->updateCompany($companyId, ['mfa_enforced' => false]);
                if (!empty(Client::unwrap($c->companies->getCompany($companyId))['mfa_enforced'])) {
                    throw new ApiError(200, null, 'MFA is still on for the company after the update.');
                }
            }
        }

        // 3. location
        $loc = $c->locations->findLocation($companyId, $spec->location->name);
        if ($loc === null) {
            $l = $spec->location;
            $loc = Client::unwrap($c->locations->createLocation($companyId, [
                'location_name' => $l->name, 'street' => $l->street, 'city' => $l->city, 'state' => $l->state,
                'country' => $l->country, 'zipcode' => $l->zipcode, 'vertical_id' => $this->verticalId($l->vertical),
                'enabled_products' => SeatDefaults::PRODUCTS,
            ]));
            $created[] = 'location';
        } else {
            $existing[] = 'location';
            if (empty($loc['is_active'])) {
                if (!$spec->reactivate) {
                    throw new FilterError(sprintf('Location "%s" exists but is inactive (set reactivate: true to reactivate it).', $spec->location->name));
                }
                $c->locations->reactivateLocation($companyId, (string) $loc['id']);
            }
            if (array_diff(SeatDefaults::PRODUCTS, $loc['enabled_products'] ?? []) !== []) {
                $c->locations->addProducts($companyId, (string) $loc['id'], SeatDefaults::PRODUCTS);
            }
        }
        $result = new SeatCustomer($companyId, (string) $loc['id']);
        $result->created = $created;
        $result->existing = $existing;
        $locationId = $result->locationId;

        // 4. groups
        $result->managerAlertsGroupId = $this->ensureGroup($locationId, SeatDefaults::MANAGER_ALERTS_GROUP, $result);
        $result->insightsHubGroupId = $this->ensureGroup($locationId, SeatDefaults::INSIGHTS_HUB_GROUP, $result);

        // 5. rules, created disabled and routed to Manager Alerts
        $groups = [$result->managerAlertsGroupId];
        $result->upsetCustomerRuleId = $this->ensureRule($locationId, SeatDefaults::upsetCustomerRule($groups), $result);
        if ($phrases !== []) {
            $threat = SeatDefaults::customerThreatRule($groups, $phrases);
        } elseif ($c->firstAlert->findRule($locationId, SeatDefaults::CUSTOMER_THREAT_RULE) !== null) {
            $threat = ['name' => SeatDefaults::CUSTOMER_THREAT_RULE, 'group_ids' => $groups]; // keep its configured phrases
        } else {
            throw new FilterError(SeatDefaults::THREAT_PHRASES_MISSING);
        }
        $result->customerThreatRuleId = $this->ensureRule($locationId, $threat, $result);

        // 6. connector (optional)
        if ($spec->connector !== null) {
            $this->ensureConnector($locationId, $spec, $result);
        }

        // 7. Insights Hub: group access + every call at the location
        $setup = Client::unwrap($c->insightsHub->getInsightsHubSetup($locationId)) ?? [];
        $access = $setup['group_access'] ?? [];
        if (!in_array($result->insightsHubGroupId, $access, true)) {
            if ($access === [] && empty($setup['connector_mappings'])) {
                $c->insightsHub->saveInsightsHubSetup($locationId, ['connector_mappings' => [], 'group_access' => [$result->insightsHubGroupId]]);
            } else {
                $c->insightsHub->addGroups($locationId, [$result->insightsHubGroupId]);
            }
        }
        $cfg = Client::unwrap($c->insightsHub->getInsightsHubConfig($locationId)) ?? [];
        if (empty($cfg['monitor_all_numbers'])) {
            $c->insightsHub->updateInsightsHubConfig($locationId, ['monitor_all_numbers' => true]);
        }

        // 8. SSO domain claim (optional): the customer publishes the TXT record, then verify
        if ($spec->ssoDomain !== null && $spec->ssoDomain !== '') {
            $dom = Client::unwrap($c->sso->claimCompanyDomain($companyId, ['domain' => $spec->ssoDomain])) ?? [];
            $result->ssoDomainId = isset($dom['id']) ? (string) $dom['id'] : null;
            $ver = $dom['verification'] ?? [];
            $result->ssoTxtRecord = ['name' => $ver['name'] ?? null, 'value' => $ver['value'] ?? null];
        }

        return $result;
    }

    /**
     * Switch on the customer's two alert rules (Upset Customer, Customer Threat). Rules already on
     * are left as they are.
     *
     * @return list<string> the ids of the rules this call switched on
     */
    public function activateSeatAlerts(SeatCustomer $customer): array
    {
        $fa = $this->c->firstAlert;
        $switched = [];
        foreach ([$customer->upsetCustomerRuleId, $customer->customerThreatRuleId] as $ruleId) {
            if ($ruleId === null || $ruleId === '') {
                continue;
            }
            $rule = Client::unwrap($fa->getAlertRule($customer->locationId, $ruleId)) ?? [];
            if (empty($rule['enabled'])) {
                $fa->updateAlertRule($customer->locationId, $ruleId, ['enabled' => true]);
                $switched[] = $ruleId;
            }
        }
        return $switched;
    }

    /** Rebuild the customer object (location, groups, rules, connector) from what is configured. */
    public function getSeatCustomer(string $companyId): SeatCustomer
    {
        $c = $this->c;
        $locations = iterator_to_array($c->locations->iterCompanyLocations($companyId), false);
        if ($locations === []) {
            throw new FilterError(sprintf('Company %s has no location.', $companyId));
        }
        $loc = $locations[0];
        foreach ($locations as $l) {
            if (!empty($l['is_active'])) {
                $loc = $l;
                break;
            }
        }
        $lid = (string) $loc['id'];
        $r = new SeatCustomer($companyId, $lid);
        $ma = $c->groups->findGroup($lid, SeatDefaults::MANAGER_ALERTS_GROUP);
        $r->managerAlertsGroupId = $ma !== null ? (string) $ma['id'] : null;
        $ih = $c->groups->findGroup($lid, SeatDefaults::INSIGHTS_HUB_GROUP);
        $r->insightsHubGroupId = $ih !== null ? (string) $ih['id'] : null;
        foreach ($c->firstAlert->iterAlertRules($lid) as $rule) {
            if (($rule['name'] ?? null) === SeatDefaults::UPSET_CUSTOMER_RULE) {
                $r->upsetCustomerRuleId = (string) $rule['id'];
            } elseif (($rule['name'] ?? null) === SeatDefaults::CUSTOMER_THREAT_RULE) {
                $r->customerThreatRuleId = (string) $rule['id'];
            }
        }
        $rank = ['active' => 0, 'connecting' => 1, 'error' => 2, 'deactivating' => 3, 'inactive' => 4];
        $conns = iterator_to_array($c->connectors->iterLocationConnectors($lid), false);
        usort($conns, static fn ($a, $b) => ($rank[$a['status'] ?? ''] ?? 5) <=> ($rank[$b['status'] ?? ''] ?? 5));
        if ($conns !== [] && ($conns[0]['status'] ?? null) !== 'inactive') {
            $r->connectorId = (string) $conns[0]['id'];
            $r->connectorStatus = $conns[0]['status'] ?? null;
            $r->connectorStateReason = $conns[0]['state_reason'] ?? null;
        }
        return $r;
    }

    // ================================================================ users

    /**
     * Create an end user with After Call on their own line, and add the line to every manager,
     * every integration user, First Alert and Insights Hub. The invitation is emailed last, once all of that has worked
     * (unless `$user->sendInvitation` is false). If the user's mapping is refused (connector not
     * active, extension monitored elsewhere) it stops there with ExtensionMappingError and no
     * invitation has been sent.
     */
    public function addFrontlineUser(SeatCustomer $customer, UserSpec $user, string $extension): SeatUser
    {
        $c = $this->c;
        $ext = trim($extension);
        if ($ext === '') {
            throw new FilterError('extension is required.');
        }
        if ($customer->connectorId === null) {
            throw new FilterError('This customer has no connector; add one before adding end users.');
        }
        $conn = $customer->connectorId;
        [$uid, $created, $status] = $this->ensureUser($customer, $user);
        $c->products->enableUserProduct($customer->locationId, $uid, SeatDefaults::AFTER_CALL);
        $c->firstAlert->setAlertEmailPreferences($customer->locationId, $uid, ['masterEnabled' => false, 'alerts' => []]);
        $this->map($customer, $uid, [$ext]);
        foreach ($this->lineHolders($customer) as $m) {
            if (!in_array($ext, $c->users->userNumbers($customer->companyId, (string) $m['id'], $conn), true)) {
                try {
                    $c->users->addNumbers($customer->companyId, (string) $m['id'], $conn, [$ext]);
                } catch (ValidationError | ConflictError $e) {
                    throw new ExtensionMappingError((string) $m['id'], $e);
                }
            }
        }
        $c->firstAlert->addMonitoredNumbers($customer->locationId, $conn, [$ext]);
        $c->insightsHub->addNumbers($customer->locationId, $conn, [$ext]);
        $invited = $this->invite($customer, $user, $uid, $status);
        return new SeatUser($uid, $user->email, 'frontline', [$ext], $created, $invited);
    }

    /**
     * Create a manager: After Call on all lines, First Alert, Insights Hub, both groups and alert
     * email on. The manager reads every end-user line at the location; their own phone line is
     * not captured on the Seat (never mapped, nor added to First Alert or Insights Hub).
     *
     * Then, with `$activateAlerts` (the default), the customer's two alert rules are switched on
     * (see activateSeatAlerts), and last the invitation is emailed (unless `$user->sendInvitation`
     * is false). If the lines cannot be mapped, ExtensionMappingError is thrown, the rules stay as
     * they were and no invitation has been sent.
     */
    public function addManager(SeatCustomer $customer, UserSpec $user, bool $activateAlerts = true): SeatUser
    {
        $c = $this->c;
        [$uid, $created, $status] = $this->ensureUser($customer, $user);
        foreach (SeatDefaults::PRODUCTS as $key) {
            $c->products->enableUserProduct($customer->locationId, $uid, $key);
        }
        $groups = array_values(array_filter([$customer->managerAlertsGroupId, $customer->insightsHubGroupId]));
        if ($groups !== []) {
            $c->groups->addUserToGroups($customer->locationId, $uid, $groups);
        }
        $prefs = $c->firstAlert->getAlertNotificationPreferences($customer->locationId, $uid) ?? [];
        $alerts = [];
        foreach ($prefs['emailNotifications']['alerts'] ?? [] as $a) {
            $alerts[] = ['alertId' => (string) $a['alertId'], 'emailEnabled' => true];
        }
        $c->firstAlert->setAlertEmailPreferences($customer->locationId, $uid, ['masterEnabled' => true, 'alerts' => $alerts]);
        $numbers = $this->lines($customer);
        if ($numbers !== []) {
            $this->map($customer, $uid, $numbers);
        }
        $activated = $activateAlerts ? $this->activateSeatAlerts($customer) : [];
        $invited = $this->invite($customer, $user, $uid, $status);
        return new SeatUser($uid, $user->email, 'manager', $numbers, $created, $invited, $activated);
    }

    /**
     * Move an end user from $old to $new: their mapping, every manager and integration user, First
     * Alert, Insights Hub.
     *
     * @return array{user_id:string, old:string, new:string}
     */
    public function changeUserExtension(SeatCustomer $customer, string $userId, string $old, string $new): array
    {
        $c = $this->c;
        $old = trim($old);
        $new = trim($new);
        if ($customer->connectorId === null) {
            throw new FilterError('This customer has no connector.');
        }
        $conn = $customer->connectorId;
        $mine = $c->users->userNumbers($customer->companyId, $userId, $conn);
        $this->map($customer, $userId, Mappings::merge(array_map(static fn ($n) => $n === $old ? $new : $n, $mine), [$new]));
        foreach ($this->lineHolders($customer) as $m) {
            if ($m['id'] === $userId) {
                continue;
            }
            $cur = $c->users->userNumbers($customer->companyId, (string) $m['id'], $conn);
            $target = Mappings::merge(array_values(array_filter($cur, static fn ($n) => $n !== $old)), [$new]);
            if ($target !== $cur) {
                $this->map($customer, (string) $m['id'], $target);
            }
        }
        $fa = $this->faNumbers($customer);
        $c->firstAlert->setMonitoredNumbers($customer->locationId, $conn, Mappings::merge(array_values(array_filter($fa, static fn ($n) => $n !== $old)), [$new]));
        $ih = $this->ihNumbers($customer);
        $c->insightsHub->setNumbers($customer->locationId, $conn, Mappings::merge(array_values(array_filter($ih, static fn ($n) => $n !== $old)), [$new]));
        return ['user_id' => $userId, 'old' => $old, 'new' => $new];
    }

    /**
     * Remove a user: leave every group → clear their number mapping → release an end user's line
     * (unless $keepExtensions) → deactivate. Products are left as they are.
     *
     * Only an end user's line is released, and only when no other active end user at the location
     * holds it: it comes off managers, integration users, First Alert and Insights Hub. Removing a
     * manager or an integration user (tagged, or any non-manager holding more than one line)
     * releases nothing, so the location's alerting and insights keep every line.
     *
     * @return array{user_id:string, released_numbers:list<string>, kind:string}
     */
    public function removeUser(SeatCustomer $customer, string $userId, bool $keepExtensions = false): array
    {
        $c = $this->c;
        $row = Client::unwrap($c->users->getLocationUser($customer->locationId, $userId)) ?? [];
        $detail = Client::unwrap($c->users->getCompanyUser($customer->companyId, $userId)) ?? [];
        $row['department'] = $detail['department'] ?? null;
        $conn = $customer->connectorId;
        $mine = $conn !== null ? $c->users->userNumbers($customer->companyId, $userId, $conn) : [];
        $kind = match (true) {
            self::isIntegration($row) => 'integration',
            self::hasAll($row, SeatDefaults::PRODUCTS) => 'manager',
            count($mine) > 1 => 'integration',
            default => 'frontline',
        };
        $released = [];
        $holders = [];
        if ($kind === 'frontline' && $conn !== null && $mine !== []) {
            $groups = $this->classify($customer);
            $held = [];
            foreach ($groups['frontline'] as $u) {
                if ((string) $u['id'] !== $userId) {
                    $held = Mappings::merge($held, $c->users->userNumbers($customer->companyId, (string) $u['id'], $conn));
                }
            }
            $released = array_values(array_diff($mine, $held));
            $holders = array_merge($groups['managers'], $groups['integration']);
        }
        $c->groups->removeUserFromGroups($customer->locationId, $userId);
        if (!empty(Client::unwrap($c->users->getUserNumberMappings($customer->companyId, $userId)))) {
            $c->users->setUserNumberMappings($customer->companyId, $userId, ['mappings' => []]);
        }
        if ($released !== [] && !$keepExtensions) {
            foreach ($holders as $m) {
                if ((string) $m['id'] === $userId) {
                    continue;
                }
                $cur = $c->users->userNumbers($customer->companyId, (string) $m['id'], $conn);
                if (array_intersect($released, $cur) !== []) {
                    $this->map($customer, (string) $m['id'], array_values(array_diff($cur, $released)));
                }
            }
            $c->firstAlert->removeMonitoredNumbers($customer->locationId, $conn, $released);
            $c->insightsHub->removeNumbers($customer->locationId, $conn, $released);
        }
        try {
            $c->users->deactivateCompanyUser($customer->companyId, $userId);
        } catch (ConflictError) {
            // already deactivated
        }
        return ['user_id' => $userId, 'released_numbers' => $keepExtensions ? [] : $released, 'kind' => $kind];
    }

    /** Active end users (After Call only, not integration users) at the customer's location. @return list<array<string,mixed>> */
    public function listFrontlineUsers(SeatCustomer $customer): array
    {
        return $this->classify($customer)['frontline'];
    }

    /** Active managers (all three products, not integration users) at the customer's location. @return list<array<string,mixed>> */
    public function listManagers(SeatCustomer $customer): array
    {
        return $this->classify($customer)['managers'];
    }

    /** Active integration users (department "integration") at the customer's location. @return list<array<string,mixed>> */
    public function listIntegrationUsers(SeatCustomer $customer): array
    {
        return $this->classify($customer)['integration'];
    }

    /**
     * Add the user an integration signs in with to read calls, alerts and insights: a frontline
     * user with password sign-in, tagged `department: "integration"`, all three products, both
     * groups, every alert email off, and mapped to all lines. Workflows never count it as an end
     * user or manager, and keep it on all lines. It never switches the alerts on. The invitation
     * (to set the password) is emailed last, as for other users.
     */
    public function addIntegrationUser(SeatCustomer $customer, UserSpec $user): SeatUser
    {
        $c = $this->c;
        if ($user->authMethod !== 'password') {
            throw new FilterError('An integration user signs in with a password: set authMethod to "password".');
        }
        [$uid, $created, $status] = $this->ensureUser($customer, $user);
        $c->users->updateCompanyUser($customer->companyId, $uid, ['department' => SeatDefaults::INTEGRATION_DEPARTMENT]);
        foreach (SeatDefaults::PRODUCTS as $key) {
            $c->products->enableUserProduct($customer->locationId, $uid, $key);
        }
        $groups = array_values(array_filter([$customer->managerAlertsGroupId, $customer->insightsHubGroupId]));
        if ($groups !== []) {
            $c->groups->addUserToGroups($customer->locationId, $uid, $groups);
        }
        $c->firstAlert->setAlertEmailPreferences($customer->locationId, $uid, ['masterEnabled' => false, 'alerts' => []]);
        $numbers = $this->lines($customer);
        if ($numbers !== []) {
            $this->map($customer, $uid, $numbers);
        }
        $invited = $this->invite($customer, $user, $uid, $status);
        return new SeatUser($uid, $user->email, 'integration', $numbers, $created, $invited);
    }

    /**
     * A customer leaves. In order: pause the After Call webhook (its URL is kept) → clear the First
     * Alert webhook (numbers kept) → stop the connector → deactivate the integration users → deactivate
     * the company (unless $deactivateCompany is false). Steps already done are skipped. Ask Tresic
     * about the retention of the customer's data.
     *
     * @return array{webhook_paused:bool, first_alert_webhook_cleared:bool, connector_deactivated:bool, integration_users_deactivated:list<string>, company_deactivated:bool}
     */
    public function offboardCustomer(SeatCustomer $customer, bool $deactivateCompany = true): array
    {
        $c = $this->c;
        $lid = $customer->locationId;
        $out = ['webhook_paused' => false, 'first_alert_webhook_cleared' => false, 'connector_deactivated' => false,
            'integration_users_deactivated' => [], 'company_deactivated' => false];
        $wh = $c->intelligence->getAfterCallWebhook($lid);
        if (is_array($wh) && !empty($wh['webhook_url']) && !empty($wh['enabled'])) {
            $c->intelligence->setAfterCallWebhook($lid, (string) $wh['webhook_url'], false);
            $out['webhook_paused'] = true;
        }
        if ($c->intelligence->getFirstAlertWebhook($lid) !== null) {
            $c->intelligence->setFirstAlertWebhook($lid, null);
            $out['first_alert_webhook_cleared'] = true;
        }
        if ($customer->connectorId !== null) {
            $conn = Client::unwrap($c->connectors->getConnector($lid, $customer->connectorId)) ?? [];
            if (!in_array($conn['status'] ?? null, ['inactive', 'deactivating'], true)) {
                try {
                    $c->connectors->deactivateConnector($lid, $customer->connectorId);
                    $out['connector_deactivated'] = true;
                } catch (ConflictError) {
                    // already inactive
                }
            }
        }
        foreach ($this->listIntegrationUsers($customer) as $u) {
            try {
                $c->users->deactivateCompanyUser($customer->companyId, (string) $u['id']);
                $out['integration_users_deactivated'][] = (string) $u['id'];
            } catch (ConflictError) {
                // already deactivated
            }
        }
        if ($deactivateCompany) {
            try {
                $c->companies->deactivateCompany($customer->companyId);
                $out['company_deactivated'] = true;
            } catch (ConflictError) {
                // already inactive
            }
        }
        return $out;
    }

    /**
     * Recompute the location's lines (every end user's extension) and push them to managers,
     * integration users, First Alert and Insights Hub. Managers and integration users get exactly
     * the lines; any other number on their mapping is removed. With $dryRun the
     * changes are returned and nothing is written.
     *
     * @return array{lines:list<string>, managers:array<string,array{add:list<string>,remove:list<string>}>, integration_users:array<string,array{add:list<string>,remove:list<string>}>, first_alert:array{add:list<string>,remove:list<string>}, insights_hub:array{add:list<string>,remove:list<string>}, applied:bool}
     */
    public function syncNumbers(SeatCustomer $customer, bool $dryRun = false): array
    {
        $c = $this->c;
        $diff = ['lines' => [], 'managers' => [], 'integration_users' => [], 'first_alert' => ['add' => [], 'remove' => []], 'insights_hub' => ['add' => [], 'remove' => []], 'applied' => false];
        if ($customer->connectorId === null) {
            return $diff;
        }
        $conn = $customer->connectorId;
        $lines = $this->lines($customer);
        $diff['lines'] = $lines;
        $fa = $this->faNumbers($customer);
        $ih = $this->ihNumbers($customer);
        $diff['first_alert'] = ['add' => array_values(array_diff($lines, $fa)), 'remove' => array_values(array_diff($fa, $lines))];
        $diff['insights_hub'] = ['add' => array_values(array_diff($lines, $ih)), 'remove' => array_values(array_diff($ih, $lines))];
        $targets = [];
        $groups = $this->classify($customer);
        foreach (['managers' => $groups['managers'], 'integration_users' => $groups['integration']] as $key => $holders) {
            foreach ($holders as $m) {
                $cur = $c->users->userNumbers($customer->companyId, (string) $m['id'], $conn);
                $target = $lines;
                $add = array_values(array_diff($target, $cur));
                $remove = array_values(array_diff($cur, $target));
                if ($add !== [] || $remove !== []) {
                    $diff[$key][(string) $m['id']] = ['add' => $add, 'remove' => $remove];
                    $targets[(string) $m['id']] = $target;
                }
            }
        }
        if ($dryRun) {
            return $diff;
        }
        foreach ($targets as $uid => $target) {
            $this->map($customer, (string) $uid, $target);
        }
        if ($diff['first_alert']['add'] !== [] || $diff['first_alert']['remove'] !== []) {
            $c->firstAlert->setMonitoredNumbers($customer->locationId, $conn, $lines);
        }
        if ($diff['insights_hub']['add'] !== [] || $diff['insights_hub']['remove'] !== []) {
            $c->insightsHub->setNumbers($customer->locationId, $conn, $lines);
        }
        $diff['applied'] = true;
        return $diff;
    }

    // ================================================================ internals

    private function verticalId(string $vertical): string
    {
        if (Query::isUuid($vertical)) {
            return $vertical;
        }
        foreach (Client::unwrap($this->c->locations->listVerticals()) ?? [] as $v) {
            if (in_array($vertical, [$v['key'] ?? null, $v['name'] ?? null], true)) {
                return (string) $v['id'];
            }
        }
        throw new FilterError(sprintf('Unknown vertical "%s": use an id or key from listVerticals().', $vertical));
    }

    private function ensureGroup(string $locationId, string $name, SeatCustomer $result): string
    {
        $g = $this->c->groups->findGroup($locationId, $name);
        if ($g === null) {
            $g = Client::unwrap($this->c->groups->createUserGroup($locationId, ['group_name' => $name]));
            $result->created[] = 'group:' . $name;
        } else {
            $result->existing[] = 'group:' . $name;
        }
        return (string) $g['id'];
    }

    /** @param array<string,mixed> $body */
    private function ensureRule(string $locationId, array $body, SeatCustomer $result): string
    {
        $fa = $this->c->firstAlert;
        $found = $fa->findRule($locationId, (string) $body['name']);
        if ($found === null) {
            $rule = Client::unwrap($fa->createAlertRule($locationId, $body));
            $result->created[] = 'rule:' . $body['name'];
            return (string) $rule['id'];
        }
        $result->existing[] = 'rule:' . $body['name'];
        $rule = Client::unwrap($fa->getAlertRule($locationId, (string) $found['id'])) ?? [];
        $have = array_map(static fn ($g) => (string) $g['id'], $rule['groups'] ?? []);
        if (array_diff($body['group_ids'], $have) !== []) {
            $fa->addRuleGroups($locationId, (string) $rule['id'], $body['group_ids']);
        }
        if (isset($body['triggers'])) {
            $current = array_map(static fn ($t) => ['trigger_type' => $t['trigger_type'] ?? null, 'config' => $t['config'] ?? null], $rule['triggers'] ?? []);
            if ($current != $body['triggers']) {
                $fa->updateAlertRule($locationId, (string) $rule['id'], ['triggers' => $body['triggers']]); // e.g. a new phrase list
            }
        }
        return (string) $rule['id'];
    }

    /**
     * Find the connector for the spec's domain (else its name) and make it match the spec. A
     * connector in `error`, `inactive`, or with another URL, domain or name gets the spec's
     * settings and key (PATCH) and is restarted; a working one is left alone.
     */
    private function ensureConnector(string $locationId, SeatCustomerSpec $spec, SeatCustomer $result): void
    {
        $cs = $spec->connector;
        $name = $cs->name ?? $spec->location->name . ' - NetSapiens';
        $conns = $this->c->connectors;
        $found = $this->findSeatConnector($locationId, $cs->domain, $name);
        $config = ['api_url' => $cs->apiUrl, 'domain' => $cs->domain, 'api_key' => $cs->apiKey];
        try {
            if ($found === null) {
                $conn = Client::unwrap($conns->createConnector($locationId, ['name' => $name, 'type' => 'netsapiens', 'config' => $config], true, $cs->timeout));
                $result->created[] = 'connector';
            } else {
                $id = (string) $found['id'];
                $status = $found['status'] ?? null;
                $cfg = is_array($found['config'] ?? null) ? $found['config'] : [];
                $same = ($cfg['api_url'] ?? null) === $cs->apiUrl
                    && strcasecmp((string) ($cfg['domain'] ?? ''), $cs->domain) === 0
                    && ($found['name'] ?? null) === $name;
                $result->existing[] = 'connector';
                if ($same && in_array($status, ['active', 'connecting'], true)) {
                    $conn = $found;
                } else {
                    $conns->updateConnector($locationId, $id, ['name' => $name, 'config' => $config]);
                    $result->existing[] = 'connector:updated';
                    if ($status === 'inactive' || $status === 'deactivating') {
                        if ($status === 'deactivating') {
                            $conns->waitForStatus($locationId, $id, ['inactive'], $cs->timeout, false);
                        }
                        $conn = Client::unwrap($conns->reactivateConnector($locationId, $id, true, $cs->timeout));
                    } else {
                        $conn = $conns->waitForStatus($locationId, $id, ['active', 'error'], $cs->timeout, false);
                        if (($conn['status'] ?? null) === 'error') {
                            $conns->deactivateConnector($locationId, $id, true, $cs->timeout);
                            $conn = Client::unwrap($conns->reactivateConnector($locationId, $id, true, $cs->timeout));
                        }
                    }
                }
            }
        } catch (ConnectorError $e) { // includes ConnectorTimeoutError
            $conn = $e->connector ?? [];
            if ($found === null) {
                $result->created[] = 'connector';
            }
        }
        $result->connectorId = isset($conn['id']) ? (string) $conn['id'] : null;
        $result->connectorStatus = $conn['status'] ?? null;
        $result->connectorStateReason = $conn['state_reason'] ?? null;
    }

    /** @return array<string,mixed>|null the location's connector for $domain (best status first), else the one named $name */
    private function findSeatConnector(string $locationId, string $domain, string $name): ?array
    {
        $rank = ['active' => 0, 'connecting' => 1, 'error' => 2, 'deactivating' => 3, 'inactive' => 4];
        $byDomain = [];
        $byName = null;
        foreach (iterator_to_array($this->c->connectors->iterLocationConnectors($locationId), false) as $row) {
            $detail = Client::unwrap($this->c->connectors->getConnector($locationId, (string) $row['id'])) ?? [];
            $detail += ['name' => $row['name'] ?? null, 'status' => $row['status'] ?? null];
            if (strcasecmp((string) ($detail['config']['domain'] ?? ''), $domain) === 0) {
                $byDomain[] = $detail;
            } elseif ($byName === null && ($detail['name'] ?? null) === $name) {
                $byName = $detail;
            }
        }
        usort($byDomain, static fn ($a, $b) => ($rank[$a['status'] ?? ''] ?? 5) <=> ($rank[$b['status'] ?? ''] ?? 5));
        return $byDomain[0] ?? $byName;
    }

    /**
     * The company to provision into, or null to create one. Never adopts a company found by name
     * alone: the primary contact email must match too.
     *
     * @return array<string,mixed>|null
     */
    private function findSeatCompany(string $resellerId, SeatCustomerSpec $spec): ?array
    {
        $companies = $this->c->companies;
        if ($spec->companyId !== null && $spec->companyId !== '') {
            return Client::unwrap($companies->getCompany($spec->companyId)) ?? [];
        }
        $same = $companies->findCompanies($resellerId, $spec->companyName);
        if ($same === []) {
            return null;
        }
        $want = strtolower(trim($spec->adminEmail));
        $matches = [];
        foreach ($same as $row) {
            $detail = Client::unwrap($companies->getCompany((string) $row['id'])) ?? [];
            if (strtolower(trim((string) ($detail['primary_admin_contact_email'] ?? ''))) === $want) {
                $matches[] = $detail;
            }
        }
        if (count($matches) === 1) {
            return $matches[0];
        }
        throw new ConflictError(
            409,
            ConflictError::COMPANY_NAME_CONFLICT,
            sprintf("A company named '%s' already exists with a different primary contact. Pass companyId to use an existing company, or choose a different company name.", $spec->companyName),
            null,
            'provisionSeatCustomer',
            [],
            null,
            true,
        );
    }

    /** Find or create the user, never emailing an invitation here. @return array{0:string, 1:bool, 2:?string} */
    private function ensureUser(SeatCustomer $customer, UserSpec $user): array
    {
        $users = $this->c->users;
        $found = $users->findUserByEmail($customer->companyId, $user->email);
        if ($found === null) {
            $body = [
                'email' => $user->email, 'first_name' => $user->firstName, 'last_name' => $user->lastName,
                'role' => 'frontline_user', 'location_ids' => [$customer->locationId],
                'send_invitation' => false, 'auth_method' => $user->authMethod,
            ];
            if ($user->phoneNumber !== null && $user->phoneNumber !== '') {
                $body['phone_number'] = $user->phoneNumber;
            }
            $row = Client::unwrap($users->createCompanyUser($customer->companyId, $body)) ?? [];
            return [(string) $row['id'], true, (string) ($row['status'] ?? 'invited')];
        }
        $uid = (string) $found['id'];
        if (($found['status'] ?? null) === 'deactivated') {
            $users->reactivateCompanyUser($customer->companyId, $uid);
        }
        $detail = Client::unwrap($users->getCompanyUser($customer->companyId, $uid)) ?? [];
        if (($detail['role'] ?? null) !== 'frontline_user') {
            throw new FilterError(sprintf('%s is a %s; Seat users are frontline users.', $user->email, $detail['role'] ?? 'user'));
        }
        $locs = array_map(static fn ($l) => (string) $l['location_id'], $detail['locations'] ?? []);
        if (!in_array($customer->locationId, $locs, true)) {
            $users->addLocations($customer->companyId, $uid, [$customer->locationId]);
        }
        return [$uid, false, isset($detail['status']) ? (string) $detail['status'] : null];
    }

    /** The last step: email the invitation to a user who has not accepted one yet. */
    private function invite(SeatCustomer $customer, UserSpec $user, string $userId, ?string $status): bool
    {
        if (!$user->sendInvitation || $status !== 'invited') {
            return false;
        }
        $this->c->users->resendInvitation($customer->companyId, $userId);
        return true;
    }

    /** @param list<string> $numbers */
    private function map(SeatCustomer $customer, string $userId, array $numbers): void
    {
        if ($customer->connectorId === null) {
            throw new FilterError('This customer has no connector; numbers cannot be mapped yet.');
        }
        try {
            $this->c->users->setNumbers($customer->companyId, $userId, $customer->connectorId, $numbers);
        } catch (ValidationError | ConflictError $e) {
            throw new ExtensionMappingError($userId, $e);
        }
    }

    /** @return list<string> the location's lines: the number of every end user holding exactly one */
    private function lines(SeatCustomer $customer): array
    {
        if ($customer->connectorId === null) {
            return [];
        }
        $out = [];
        foreach ($this->listFrontlineUsers($customer) as $u) {
            $nums = $this->c->users->userNumbers($customer->companyId, (string) $u['id'], $customer->connectorId);
            if (count($nums) === 1) {
                $out = Mappings::merge($out, $nums);
            }
        }
        return $out;
    }

    /** @return list<array<string,mixed>> managers and integration users: everyone kept on all lines */
    private function lineHolders(SeatCustomer $customer): array
    {
        $g = $this->classify($customer);
        return array_merge($g['managers'], $g['integration']);
    }

    /** @return array{frontline:list<array<string,mixed>>, managers:list<array<string,mixed>>, integration:list<array<string,mixed>>} */
    private function classify(SeatCustomer $customer): array
    {
        $out = ['frontline' => [], 'managers' => [], 'integration' => []];
        foreach ($this->rows($customer) as $r) {
            if (self::isIntegration($r)) {
                if (self::active($r)) {
                    $out['integration'][] = $r;
                }
            } elseif (self::isManager($r)) {
                $out['managers'][] = $r;
            } elseif (self::isFrontline($r)) {
                $out['frontline'][] = $r;
            }
        }
        return $out;
    }

    /** True for a user tagged `department: "integration"`. @param array<string,mixed> $r */
    public static function isIntegration(array $r): bool
    {
        return strcasecmp(trim((string) ($r['department'] ?? '')), SeatDefaults::INTEGRATION_DEPARTMENT) === 0;
    }

    /** Active frontline user with all three products, not an integration user. @param array<string,mixed> $r */
    public static function isManager(array $r): bool
    {
        return self::active($r) && !self::isIntegration($r) && self::hasAll($r, SeatDefaults::PRODUCTS);
    }

    /** Active frontline user with After Call only, not an integration user. @param array<string,mixed> $r */
    public static function isFrontline(array $r): bool
    {
        return self::active($r) && !self::isIntegration($r) && self::products($r) === [SeatDefaults::AFTER_CALL];
    }

    /** @return list<string> */
    private function faNumbers(SeatCustomer $customer): array
    {
        $cfg = Client::unwrap($this->c->firstAlert->getFirstAlertConfig($customer->locationId)) ?? [];
        return Mappings::numbersFor($cfg['connector_mappings'] ?? [], $customer->connectorId);
    }

    /** @return list<string> */
    private function ihNumbers(SeatCustomer $customer): array
    {
        $s = Client::unwrap($this->c->insightsHub->getInsightsHubSetup($customer->locationId)) ?? [];
        return Mappings::numbersFor($s['connector_mappings'] ?? [], $customer->connectorId);
    }

    /**
     * The location's users with their company-user `department` joined in, minus excluded users.
     *
     * @return list<array<string,mixed>>
     */
    private function rows(SeatCustomer $customer): array
    {
        $dept = [];
        foreach ($this->c->users->iterCompanyUsers($customer->companyId, ['location_id' => $customer->locationId]) as $u) {
            $dept[(string) ($u['id'] ?? '')] = $u['department'] ?? null;
        }
        $exIds = array_flip($customer->excludeUserIds);
        $exEmails = array_flip(array_map(static fn ($e) => strtolower(trim((string) $e)), $customer->excludeEmails));
        $out = [];
        foreach ($this->c->users->iterLocationUsers($customer->locationId) as $r) {
            $id = (string) ($r['id'] ?? '');
            if (isset($exIds[$id]) || isset($exEmails[strtolower(trim((string) ($r['email'] ?? '')))])) {
                continue;
            }
            $r['department'] = $dept[$id] ?? null;
            $out[] = $r;
        }
        return $out;
    }

    /** @param array<string,mixed> $r */
    private static function active(array $r): bool
    {
        return ($r['status'] ?? null) !== 'deactivated' && ($r['role'] ?? null) === 'frontline_user';
    }

    /** @param array<string,mixed> $r @return list<string> */
    private static function products(array $r): array
    {
        $p = $r['enabled_products'] ?? [];
        $p = is_array($p) ? array_values(array_unique($p)) : [];
        sort($p);
        return $p;
    }

    /** @param array<string,mixed> $r @param list<string> $keys */
    private static function hasAll(array $r, array $keys): bool
    {
        return array_diff($keys, self::products($r)) === [];
    }
}
