<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api;

use IntelligenceCloud\Api\Generated\FirstAlertApiBase;
use IntelligenceCloud\Client;
use IntelligenceCloud\Exception\FilterError;

/**
 * First Alert operations. The location config PUT is a full replacement that clears
 * `webhook_url` when it is left out: the number helpers always resend it.
 */
final class FirstAlertApi extends FirstAlertApiBase
{
    /**
     * Full replacement of monitored numbers AND webhook_url. `webhook_url` must be present (null
     * clears it). Prefer the add/remove/set helpers.
     *
     * @param array<string,mixed> $body
     * @return array<string,mixed>|null
     */
    public function saveFirstAlertConfig(string $locationId, array $body): ?array
    {
        if (!array_key_exists('webhook_url', $body)) {
            throw new FilterError('saveFirstAlertConfig: send webhook_url every time (null to clear). The API clears it when it is left out.');
        }
        return parent::saveFirstAlertConfig($locationId, $body);
    }

    /**
     * Add numbers to First Alert at the location. Keeps the webhook URL and other numbers.
     *
     * @param iterable<string> $numbers
     * @return array<string,mixed>|null
     */
    public function addMonitoredNumbers(string $locationId, string $connectorId, iterable $numbers): ?array
    {
        $cfg = $this->config($locationId);
        return $this->save($locationId, $cfg, Mappings::edit($cfg['connector_mappings'] ?? [], $connectorId, $numbers));
    }

    /**
     * @param iterable<string> $numbers
     * @return array<string,mixed>|null
     */
    public function removeMonitoredNumbers(string $locationId, string $connectorId, iterable $numbers): ?array
    {
        $cfg = $this->config($locationId);
        return $this->save($locationId, $cfg, Mappings::edit($cfg['connector_mappings'] ?? [], $connectorId, [], $numbers));
    }

    /**
     * Replace the numbers for one connector. Keeps the webhook URL and other connectors' numbers.
     *
     * @param iterable<string> $numbers
     * @return array<string,mixed>|null
     */
    public function setMonitoredNumbers(string $locationId, string $connectorId, iterable $numbers): ?array
    {
        $cfg = $this->config($locationId);
        $others = array_values(array_filter($cfg['connector_mappings'] ?? [], static fn ($m) => ($m['connector_id'] ?? null) !== $connectorId));
        return $this->save($locationId, $cfg, Mappings::edit($others, $connectorId, $numbers));
    }

    /**
     * Route a rule to more groups (read, merge, PATCH `group_ids`).
     *
     * @param iterable<string> $groupIds
     * @return array<string,mixed>|null
     */
    public function addRuleGroups(string $locationId, string $ruleId, iterable $groupIds): ?array
    {
        return $this->updateAlertRule($locationId, $ruleId, ['group_ids' => Mappings::merge($this->ruleGroupIds($locationId, $ruleId), $groupIds)]);
    }

    /**
     * @param iterable<string> $groupIds
     * @return array<string,mixed>|null
     */
    public function removeRuleGroups(string $locationId, string $ruleId, iterable $groupIds): ?array
    {
        return $this->updateAlertRule($locationId, $ruleId, ['group_ids' => Mappings::merge($this->ruleGroupIds($locationId, $ruleId), [], $groupIds)]);
    }

    /** @return array<string,mixed>|null the rule with exactly this name (list row) */
    public function findRule(string $locationId, string $name): ?array
    {
        foreach ($this->iterAlertRules($locationId) as $r) {
            if (($r['name'] ?? null) === $name) {
                return $r;
            }
        }
        return null;
    }

    /** @return array<string,mixed> */
    private function config(string $locationId): array
    {
        return Client::unwrap($this->getFirstAlertConfig($locationId)) ?? [];
    }

    /**
     * @param array<string,mixed> $cfg
     * @param list<array<string,mixed>> $mappings
     * @return array<string,mixed>|null
     */
    private function save(string $locationId, array $cfg, array $mappings): ?array
    {
        return $this->saveFirstAlertConfig($locationId, ['webhook_url' => $cfg['webhook_url'] ?? null, 'connector_mappings' => $mappings]);
    }

    /** @return list<string> */
    private function ruleGroupIds(string $locationId, string $ruleId): array
    {
        $rule = Client::unwrap($this->getAlertRule($locationId, $ruleId)) ?? [];
        return array_values(array_map(static fn ($g) => (string) $g['id'], $rule['groups'] ?? []));
    }
}
