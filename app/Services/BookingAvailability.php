<?php

namespace App\Services;

use App\Exceptions\BookingConflictException;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\ResourceBlockedPeriod;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Central availability engine. All booking timing rules and conflict detection
 * live here so they are enforced consistently on every code path.
 */
class BookingAvailability
{
    public const SLOT_MINUTES = 30;

    /**
     * Find a conflicting booking for a resource in the given window.
     */
    public function conflictingBooking(Resource $resource, CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): ?Booking
    {
        $buffer = $this->bufferMinutes($resource);

        return Booking::query()
            ->booked()
            ->overlapping(
                Carbon::parse($start)->subMinutes($buffer),
                Carbon::parse($end)->addMinutes($buffer),
            )
            ->whereHas('resources', fn ($query) => $query->whereKey($resource->getKey()))
            ->when($ignoreBookingId, fn ($query) => $query->whereKeyNot($ignoreBookingId))
            ->orderBy('starts_at')
            ->first();
    }

    /**
     * Find a blocked/maintenance period overlapping the window.
     */
    public function blockedPeriod(Resource $resource, CarbonInterface $start, CarbonInterface $end): ?ResourceBlockedPeriod
    {
        return ResourceBlockedPeriod::query()
            ->where('resource_id', $resource->getKey())
            ->overlapping($start, $end)
            ->orderBy('starts_at')
            ->first();
    }

    /**
     * @throws BookingConflictException
     */
    public function assertAvailable(Resource $resource, CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): void
    {
        if (! $resource->isBookable()) {
            throw new BookingConflictException(
                $resource,
                message: __('":resource" is not currently available for booking.', ['resource' => $resource->name]),
            );
        }

        if ($blocked = $this->blockedPeriod($resource, $start, $end)) {
            throw new BookingConflictException(
                $resource,
                startsAt: $blocked->starts_at,
                endsAt: $blocked->ends_at,
                message: __('":resource" is unavailable: :reason', [
                    'resource' => $resource->name,
                    'reason' => $blocked->reason ?: $blocked->type->label(),
                ]),
            );
        }

        if ($conflict = $this->conflictingBooking($resource, $start, $end, $ignoreBookingId)) {
            throw new BookingConflictException(
                $resource,
                conflictingBooking: $conflict,
                startsAt: $conflict->starts_at,
                endsAt: $conflict->ends_at,
                message: __('":resource" is already booked :from–:to.', [
                    'resource' => $resource->name,
                    'from' => $conflict->starts_at->format('d M Y H:i'),
                    'to' => $conflict->ends_at->format('H:i'),
                ]),
            );
        }
    }

    /**
     * Enforce duration, notice, advance-booking, working-hours and daily-limit rules.
     *
     * @throws BookingException
     */
    public function assertWithinRules(Resource $resource, CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): void
    {
        if ($end->lessThanOrEqualTo($start)) {
            throw new BookingException(__('The end time must be after the start time.'));
        }

        $minutes = (int) Carbon::parse($start)->diffInMinutes($end);

        $min = (int) $resource->rule('min_duration_minutes', Setting::get('min_booking_minutes', 15));
        $max = (int) $resource->rule('max_duration_minutes', Setting::get('max_booking_minutes', 0));

        if ($min > 0 && $minutes < $min) {
            throw new BookingException(__('The minimum booking duration is :minutes minutes.', ['minutes' => $min]));
        }

        if ($max > 0 && $minutes > $max) {
            throw new BookingException(__('The maximum booking duration is :minutes minutes.', ['minutes' => $max]));
        }

        $notice = (int) $resource->rule('min_notice_minutes', Setting::get('min_notice_minutes', 0));

        if ($notice > 0 && Carbon::parse($start)->lt(now()->addMinutes($notice))) {
            throw new BookingException(__('Bookings require at least :minutes minutes notice.', ['minutes' => $notice]));
        }

        $advance = (int) $resource->rule('max_advance_days', Setting::get('advance_booking_days', 90));

        if ($advance > 0 && Carbon::parse($start)->gt(now()->addDays($advance)->endOfDay())) {
            throw new BookingException(__('Bookings can be made at most :days days in advance.', ['days' => $advance]));
        }

        $maxPerDay = (int) $resource->rule('max_bookings_per_day', 0);

        if ($maxPerDay > 0 && $this->bookingsOnDay($resource, Carbon::parse($start), $ignoreBookingId) >= $maxPerDay) {
            throw new BookingException(__('":resource" already has the maximum of :max booking(s) on :date.', [
                'resource' => $resource->name,
                'max' => $maxPerDay,
                'date' => Carbon::parse($start)->format('d M Y'),
            ]));
        }

        if (! $this->withinWorkingHours($resource, $start, $end)) {
            throw new BookingException(__('":resource" can only be booked between :from and :to on its available days.', [
                'resource' => $resource->name,
                'from' => Carbon::parse($resource->workingWindow()[0])->format('H:i'),
                'to' => Carbon::parse($resource->workingWindow()[1])->format('H:i'),
            ]));
        }
    }

    /**
     * Working-hours check applies to single-day bookings; multi-day bookings
     * (e.g. vehicles) are only conflict/block checked.
     */
    public function withinWorkingHours(Resource $resource, CarbonInterface $start, CarbonInterface $end): bool
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        if (! $start->isSameDay($end)) {
            return true;
        }

        [$from, $to, $days] = $resource->workingWindow();

        if (! in_array((int) $start->dayOfWeekIso, $days, true)) {
            return false;
        }

        return $start->format('H:i') >= substr($from, 0, 5)
            && $end->format('H:i') <= substr($to, 0, 5);
    }

    public function bufferMinutes(Resource $resource): int
    {
        return max(0, (int) $resource->rule('buffer_minutes', 0));
    }

    /**
     * Count active bookings for a resource on a given day (excluding an edit target).
     */
    public function bookingsOnDay(Resource $resource, CarbonInterface $day, ?int $ignoreBookingId = null): int
    {
        return Booking::query()
            ->booked()
            ->whereHas('resources', fn ($query) => $query->whereKey($resource->getKey()))
            ->whereDate('starts_at', $day->toDateString())
            ->when($ignoreBookingId, fn ($query) => $query->whereKeyNot($ignoreBookingId))
            ->count();
    }

    public function isAvailable(Resource $resource, CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): bool
    {
        if (! $resource->isBookable()) {
            return false;
        }

        if ($this->blockedPeriod($resource, $start, $end)) {
            return false;
        }

        return $this->conflictingBooking($resource, $start, $end, $ignoreBookingId) === null;
    }

    /**
     * Build 30-minute slots for a resource on a date, marking each as available or not.
     *
     * Loads the day's bookings and blocked periods once (instead of querying
     * per slot) and evaluates availability in memory.
     *
     * @return array<int, array{start: Carbon, end: Carbon, available: bool}>
     */
    public function slotsForDate(Resource $resource, CarbonInterface $date, ?int $ignoreBookingId = null): array
    {
        [$from, $to, $days] = $resource->workingWindow();
        $day = Carbon::parse($date);
        $isOpenDay = in_array((int) $day->dayOfWeekIso, $days, true);
        $bookable = $resource->isBookable();

        $cursor = $day->copy()->setTimeFromTimeString($from);
        $endOfWindow = $day->copy()->setTimeFromTimeString($to);

        $buffer = $this->bufferMinutes($resource);

        $bookings = $bookable
            ? Booking::query()
                ->booked()
                ->whereHas('resources', fn ($query) => $query->whereKey($resource->getKey()))
                ->overlapping($cursor->copy()->subMinutes($buffer), $endOfWindow->copy()->addMinutes($buffer))
                ->when($ignoreBookingId, fn ($query) => $query->whereKeyNot($ignoreBookingId))
                ->get(['id', 'starts_at', 'ends_at'])
            : collect();

        $blocks = ResourceBlockedPeriod::query()
            ->where('resource_id', $resource->getKey())
            ->overlapping($cursor, $endOfWindow)
            ->get(['starts_at', 'ends_at']);

        $slots = [];
        $slotCursor = $cursor->copy();

        while ($slotCursor->lt($endOfWindow)) {
            $slotEnd = $slotCursor->copy()->addMinutes(self::SLOT_MINUTES);

            if ($slotEnd->gt($endOfWindow)) {
                $slotEnd = $endOfWindow->copy();
            }

            $available = $isOpenDay
                && $bookable
                && ! $this->overlapsAny($blocks, $slotCursor, $slotEnd)
                && ! $this->overlapsAny(
                    $bookings,
                    $slotCursor->copy()->subMinutes($buffer),
                    $slotEnd->copy()->addMinutes($buffer),
                );

            $slots[] = [
                'start' => $slotCursor->copy(),
                'end' => $slotEnd->copy(),
                'available' => $available,
            ];

            $slotCursor = $slotEnd;
        }

        return $slots;
    }

    /**
     * @param  Collection<int, Model>  $intervals
     */
    protected function overlapsAny(Collection $intervals, CarbonInterface $start, CarbonInterface $end): bool
    {
        return $intervals->contains(
            fn ($interval): bool => $interval->starts_at->lt($end) && $interval->ends_at->gt($start)
        );
    }

    /**
     * @param  Collection<int, resource>  $resources
     * @return Collection<int, BookingConflictException>
     */
    public function conflictsFor(Collection $resources, CarbonInterface $start, CarbonInterface $end, ?int $ignoreBookingId = null): Collection
    {
        return $resources->map(function (Resource $resource) use ($start, $end, $ignoreBookingId): ?BookingConflictException {
            try {
                $this->assertAvailable($resource, $start, $end, $ignoreBookingId);

                return null;
            } catch (BookingConflictException $exception) {
                return $exception;
            }
        })->filter()->values();
    }
}
