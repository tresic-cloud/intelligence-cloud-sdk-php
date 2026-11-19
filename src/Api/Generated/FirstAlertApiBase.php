<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** FirstAlert operations, generated from openapi.yaml. Do not edit by hand. */
class FirstAlertApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** List a location's alert rules. `GET /locations/{locationId}/first-alert/rules` */
    public function listAlertRules(string $locationId, array $filters = []): Page
    {
        return $this->c->page('listAlertRules', ['locationId' => $locationId], $filters);
    }

    /** Every row of listAlertRules(), following cursors 100 at a time. @return \Generator<int, array<string,mixed>> */
    public function iterAlertRules(string $locationId, array $filters = []): \Generator
    {
        return $this->c->iterate('listAlertRules', ['locationId' => $locationId], $filters);
    }

    /** Create an alert rule. `POST /locations/{locationId}/first-alert/rules` @return array<string,mixed>|null */
    public function createAlertRule(string $locationId, array $body): ?array
    {
        return $this->c->call('createAlertRule', ['locationId' => $locationId], [], $body);
    }

    /** Read an alert rule. `GET /locations/{locationId}/first-alert/rules/{ruleId}` @return array<string,mixed>|null */
    public function getAlertRule(string $locationId, string $ruleId): ?array
    {
        return $this->c->call('getAlertRule', ['locationId' => $locationId, 'ruleId' => $ruleId], [], null);
    }

    /** Update an alert rule. `PATCH /locations/{locationId}/first-alert/rules/{ruleId}` @return array<string,mixed>|null */
    public function updateAlertRule(string $locationId, string $ruleId, array $body): ?array
    {
        return $this->c->call('updateAlertRule', ['locationId' => $locationId, 'ruleId' => $ruleId], [], $body);
    }

    /** Delete an alert rule. `DELETE /locations/{locationId}/first-alert/rules/{ruleId}` @return array<string,mixed>|null */
    public function deleteAlertRule(string $locationId, string $ruleId): ?array
    {
        return $this->c->call('deleteAlertRule', ['locationId' => $locationId, 'ruleId' => $ruleId], [], null);
    }

    /** Copy an alert rule. `POST /locations/{locationId}/first-alert/rules/{ruleId}/duplicate` @return array<string,mixed>|null */
    public function duplicateAlertRule(string $locationId, string $ruleId): ?array
    {
        return $this->c->call('duplicateAlertRule', ['locationId' => $locationId, 'ruleId' => $ruleId], [], null);
    }

    /** Read monitored numbers and the alert webhook. `GET /locations/{locationId}/first-alert/config` @return array<string,mixed>|null */
    public function getFirstAlertConfig(string $locationId): ?array
    {
        return $this->c->call('getFirstAlertConfig', ['locationId' => $locationId], [], null);
    }

    /** Set monitored numbers and the alert webhook. `PUT /locations/{locationId}/first-alert/config` @return array<string,mixed>|null */
    public function saveFirstAlertConfig(string $locationId, array $body): ?array
    {
        return $this->c->call('saveFirstAlertConfig', ['locationId' => $locationId], [], $body);
    }

    /** Test the alert webhook. `POST /locations/{locationId}/first-alert/config/test-webhook` @return array<string,mixed>|null */
    public function testFirstAlertWebhook(string $locationId, array $body): ?array
    {
        return $this->c->call('testFirstAlertWebhook', ['locationId' => $locationId], [], $body);
    }

    /** Read a user's alert email and digest. `GET /locations/{locationId}/users/{userId}/first-alert/notification-preferences` @return array<string,mixed>|null */
    public function getAlertNotificationPreferences(string $locationId, string $userId): ?array
    {
        return $this->c->call('getAlertNotificationPreferences', ['locationId' => $locationId, 'userId' => $userId], [], null);
    }

    /** Set a user's alert email. `PUT /locations/{locationId}/users/{userId}/first-alert/email-preferences` @return array<string,mixed>|null */
    public function setAlertEmailPreferences(string $locationId, string $userId, array $body): ?array
    {
        return $this->c->call('setAlertEmailPreferences', ['locationId' => $locationId, 'userId' => $userId], [], $body);
    }

    /** Set a user's alert digest. `PUT /locations/{locationId}/users/{userId}/first-alert/digest-preferences` @return array<string,mixed>|null */
    public function setAlertDigestPreferences(string $locationId, string $userId, array $body): ?array
    {
        return $this->c->call('setAlertDigestPreferences', ['locationId' => $locationId, 'userId' => $userId], [], $body);
    }
}
