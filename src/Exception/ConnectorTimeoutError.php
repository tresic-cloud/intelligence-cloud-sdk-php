<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/** The connector did not reach a final status in time. Check it again later with getConnector. */
class ConnectorTimeoutError extends ConnectorError
{
    /** @param array<string,mixed> $connector */
    public static function after(array $connector, float $waited): self
    {
        return new self(
            sprintf('Connector %s still "%s" after %d s', $connector['id'] ?? '?', $connector['status'] ?? 'unknown', (int) $waited),
            isset($connector['state_reason']) && is_string($connector['state_reason']) ? $connector['state_reason'] : null,
            isset($connector['status']) ? (string) $connector['status'] : null,
            $connector,
        );
    }
}
