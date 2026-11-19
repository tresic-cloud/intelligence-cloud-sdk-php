<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/**
 * An error response from the API.
 *
 * `$status` is the HTTP status (0 when no response arrived) and `$errorCode` the normalised error
 * code from `error.code`, `error.type` or the flat edge shape.
 *
 * The exception text (getMessage()) never contains the server's own message: it is
 * "HTTP <status> <code> (request_id <id>)" plus text the SDK wrote itself. The server's message is
 * kept in `$serverMessage` (null when the SDK replaced it). Log status, code and request_id, not
 * exception text or response bodies. Show your own message to end users.
 */
class ApiError extends IntelligenceCloudError
{
    /** The API's own error message, when the SDK did not replace it. Do not log or display it. */
    public readonly ?string $serverMessage;

    /**
     * @param string $message the server's message, or (with $sdkText) text written by the SDK
     * @param bool $sdkText true when $message was written by the SDK and is safe to show in the exception text
     */
    public function __construct(
        public readonly int $status,
        public readonly ?string $errorCode,
        string $message,
        public readonly ?string $requestId = null,
        public readonly ?string $operationId = null,
        ?\Throwable $previous = null,
        bool $sdkText = false,
    ) {
        $this->serverMessage = $sdkText ? null : $message;
        parent::__construct($this->describe($sdkText ? $message : static::hint()), $status, $previous);
    }

    /** SDK-written text added when the server's message is held back. */
    protected static function hint(): ?string
    {
        return null;
    }

    protected function describe(?string $text): string
    {
        $out = 'HTTP ' . $this->status;
        if ($this->errorCode !== null && $this->errorCode !== '') {
            $out .= ' ' . $this->errorCode;
        }
        if ($text !== null && $text !== '') {
            $out .= ': ' . $text;
        }
        if ($this->requestId !== null && $this->requestId !== '') {
            $out .= ' (request_id ' . $this->requestId . ')';
        }
        return $out;
    }
}
