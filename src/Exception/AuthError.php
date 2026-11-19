<?php

declare(strict_types=1);

namespace IntelligenceCloud\Exception;

/** 401: token missing, invalid or expired, or the sign-in was refused. */
class AuthError extends ApiError
{
}
