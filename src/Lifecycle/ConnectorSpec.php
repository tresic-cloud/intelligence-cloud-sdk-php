<?php

declare(strict_types=1);

namespace IntelligenceCloud\Lifecycle;

/** The phone-system connector. `$apiUrl` is the full API root ending in /ns-api/v2; the key must be read/write. */
final class ConnectorSpec
{
    public function __construct(
        public readonly string $apiUrl,
        public readonly string $domain,
        #[\SensitiveParameter] public readonly string $apiKey,
        public readonly ?string $name = null,   // default "<location name> - NetSapiens"
        public readonly float $timeout = 600.0, // seconds to wait for the connector to settle
    ) {
    }

    /** @return array<string,string> */
    public function __debugInfo(): array
    {
        return ['apiUrl' => $this->apiUrl, 'domain' => $this->domain, 'apiKey' => '<redacted>', 'name' => (string) $this->name];
    }
}
