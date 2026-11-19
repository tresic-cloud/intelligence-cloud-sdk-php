<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/**
 * A filter, body field or argument the API does not support. Raised before any request is sent:
 * the API silently ignores unknown query parameters and would return unfiltered data.
 */
class FilterError extends IntelligenceCloudError
{
}
