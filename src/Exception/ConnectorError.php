<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/**
 * A connector ended in `error`. `$stateReason` says why, for example `invalid_permissions`
 * (the phone-system key lacks the permissions the connector needs; use a read/write key).
 */
class ConnectorError extends IntelligenceCloudError
{
    public readonly ?string $connectorId;
    public readonly ?string $statusMessage;

    /** @param array<string,mixed>|null $connector the connector as last read */
    public function __construct(
        string $message,
        public readonly ?string $stateReason = null,
        public readonly ?string $connectorStatus = null,
        public readonly ?array $connector = null,
        ?\Throwable $previous = null,
    ) {
        $this->connectorId = isset($connector['id']) ? (string) $connector['id'] : null;
        $this->statusMessage = isset($connector['status_message']) && is_string($connector['status_message']) ? $connector['status_message'] : null;
        parent::__construct($message, 0, $previous);
    }

    /** @param array<string,mixed> $connector */
    public static function fromConnector(array $connector): self
    {
        $reason = isset($connector['state_reason']) && is_string($connector['state_reason']) ? $connector['state_reason'] : null;
        return new self(
            sprintf('Connector %s is in error: %s', $connector['id'] ?? '?', $reason ?? 'unknown reason'),
            $reason,
            isset($connector['status']) ? (string) $connector['status'] : null,
            $connector,
        );
    }
}
