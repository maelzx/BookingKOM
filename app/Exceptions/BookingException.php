<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a booking action violates a business rule (timing, limits, state).
 */
class BookingException extends RuntimeException
{
    //
}
