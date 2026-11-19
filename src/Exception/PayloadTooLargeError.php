<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/** The webhook body is larger than the parser accepts (Webhooks::MAX_EVENT_BYTES by default). Answer 413. */
class PayloadTooLargeError extends WebhookPayloadError
{
}
