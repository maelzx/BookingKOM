<?php

namespace App\Services;

use App\Enums\BookingStatus;
use InvalidArgumentException;

/**
 * Single source of truth for booking status transitions.
 *
 * Every feature that changes a booking's status must route through here so the
 * lifecycle (Pending → Approved → Confirmed → Completed) stays consistent.
 */
class BookingStatusTransition
{
    /**
     * @var array<string, array<int, BookingStatus>>
     */
    protected const TRANSITIONS = [
        'pending' => [BookingStatus::Approved, BookingStatus::Confirmed, BookingStatus::Rejected, BookingStatus::Cancelled],
        'approved' => [BookingStatus::Confirmed, BookingStatus::Rejected, BookingStatus::Cancelled],
        'confirmed' => [BookingStatus::Completed, BookingStatus::Cancelled, BookingStatus::NoShow],
        'completed' => [],
        'rejected' => [],
        'cancelled' => [],
        'no_show' => [],
    ];

    /**
     * @return array<int, BookingStatus>
     */
    public function transitionsFor(BookingStatus $from): array
    {
        return self::TRANSITIONS[$from->value] ?? [];
    }

    public function canTransition(BookingStatus $from, BookingStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return in_array($to, $this->transitionsFor($from), true);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function assertCanTransition(BookingStatus $from, BookingStatus $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException(
                sprintf('Cannot transition booking status from "%s" to "%s".', $from->value, $to->value)
            );
        }
    }
}
