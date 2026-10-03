<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Confirmed => 'Confirmed',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::NoShow => 'No-show',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Approved => 'bg-blue-100 text-blue-800',
            self::Confirmed => 'bg-green-100 text-green-800',
            self::Completed => 'bg-gray-100 text-gray-800',
            self::Rejected => 'bg-red-100 text-red-800',
            self::Cancelled => 'bg-base-200 text-base-content/70',
            self::NoShow => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Statuses that occupy the resource and therefore block other bookings.
     */
    public function blocksAvailability(): bool
    {
        return in_array($this, [self::Pending, self::Approved, self::Confirmed], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Rejected, self::Cancelled, self::NoShow], true);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Approved, self::Confirmed], true);
    }

    /**
     * Whether the booking can still be cancelled by its organiser.
     */
    public function isCancellable(): bool
    {
        return in_array($this, [self::Pending, self::Approved, self::Confirmed], true);
    }

    /**
     * Statuses that count as successful bookings for utilisation reporting.
     */
    public static function fulfilled(): array
    {
        return [self::Confirmed, self::Completed];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
