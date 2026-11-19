<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Resellers operations, generated from openapi.yaml. Do not edit by hand. */
class ResellersApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** Read reseller branding. `GET /resellers/{resellerId}/branding` @return array<string,mixed>|null */
    public function getResellerBranding(string $resellerId): ?array
    {
        return $this->c->call('getResellerBranding', ['resellerId' => $resellerId], [], null);
    }

    /** Update reseller branding. `PATCH /resellers/{resellerId}/branding` @return array<string,mixed>|null */
    public function updateResellerBranding(string $resellerId, array $body): ?array
    {
        return $this->c->call('updateResellerBranding', ['resellerId' => $resellerId], [], $body);
    }

    /** Read outbound email (SMTP) settings. `GET /resellers/{resellerId}/smtp-settings` @return array<string,mixed>|null */
    public function getResellerSmtpSettings(string $resellerId): ?array
    {
        return $this->c->call('getResellerSmtpSettings', ['resellerId' => $resellerId], [], null);
    }

    /** Save outbound email (SMTP) settings. `PATCH /resellers/{resellerId}/smtp-settings` @return array<string,mixed>|null */
    public function saveResellerSmtpSettings(string $resellerId, array $body): ?array
    {
        return $this->c->call('saveResellerSmtpSettings', ['resellerId' => $resellerId], [], $body);
    }

    /** Remove outbound email (SMTP) settings. `DELETE /resellers/{resellerId}/smtp-settings` @return array<string,mixed>|null */
    public function deleteResellerSmtpSettings(string $resellerId): ?array
    {
        return $this->c->call('deleteResellerSmtpSettings', ['resellerId' => $resellerId], [], null);
    }
}
