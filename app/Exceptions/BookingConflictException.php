<?php

namespace App\Exceptions;

use App\Models\Booking;
use App\Models\Resource;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Raised when a booking would overlap an existing booking or blocked period.
 */
class BookingConflictException extends RuntimeException
{
    public function __construct(
        public readonly Resource $resource,
        public readonly ?Booking $conflictingBooking = null,
        public readonly ?CarbonInterface $startsAt = null,
        public readonly ?CarbonInterface $endsAt = null,
        ?string $message = null,
    ) {
        parent::__construct($message ?? __('":resource" is not available for the selected time.', [
            'resource' => $resource->name,
        ]));
    }
}
