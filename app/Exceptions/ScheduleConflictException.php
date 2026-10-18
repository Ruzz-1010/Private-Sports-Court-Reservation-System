<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a requested interval cannot be booked.
 *
 * The message is safe to show to a customer: it explains the conflict without
 * leaking other customers' booking details.
 */
class ScheduleConflictException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
