<?php

namespace App\Services;

use App\Enums\ApprovalMode;
use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Exceptions\BookingConflictException;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\BookingApprovalRequired;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingCreated;
use App\Notifications\BookingDecision;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Orchestrates the booking lifecycle: creation (including recurrence and
 * conflict checks), approvals, cancellation and completion.
 */
class BookingService
{
    public function __construct(
        private readonly BookingAvailability $availability,
        private readonly BookingStatusTransition $transitions,
        private readonly RecurrenceService $recurrence,
        private readonly int $lockWaitSeconds = 5,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>  $resourceIds
     * @param  array<int, int>  $attendeeIds
     */
    public function create(User $organiser, array $attributes, array $resourceIds, array $attendeeIds = []): Booking
    {
        $start = Carbon::parse($attributes['starts_at']);
        $end = Carbon::parse($attributes['ends_at']);

        $resources = $this->resolveResources($resourceIds);

        $occurrences = $this->buildOccurrences($start, $end, $attributes['recurrence'] ?? null);

        return $this->withResourceLocks($resources->pluck('id')->all(), function () use ($organiser, $attributes, $resources, $attendeeIds, $occurrences): Booking {
            $this->assertWindowIsAvailable($resources, $occurrences);

            return DB::transaction(function () use ($organiser, $attributes, $resources, $attendeeIds, $occurrences): Booking {
                $requiresApproval = $resources->contains(fn (Resource $resource): bool => $resource->requiresApproval());
                $status = $requiresApproval ? BookingStatus::Pending : BookingStatus::Confirmed;

                $parent = null;

                foreach ($occurrences as $index => $occurrence) {
                    $booking = Booking::create([
                        'title' => $attributes['title'],
                        'purpose' => $attributes['purpose'] ?? null,
                        'user_id' => $organiser->id,
                        'department' => $attributes['department'] ?? null,
                        'starts_at' => $occurrence['start'],
                        'ends_at' => $occurrence['end'],
                        'status' => $status,
                        'recurrence_rule' => $index === 0 ? ($attributes['recurrence'] ?? null) : null,
                        'recurrence_parent_id' => $parent?->id,
                        'occurrence_index' => count($occurrences) > 1 ? $index : null,
                    ]);

                    $parent ??= $booking;

                    $this->attachResources($booking, $resources);
                    $this->attachAttendees($booking, $organiser, $attendeeIds);
                }

                $parent->load('resources.manager', 'attendees');

                $this->notifyCreated($parent);

                if ($requiresApproval) {
                    $this->notifyApprovers($parent);
                }

                return $parent->loadCount('occurrences')->load('resources', 'attendees', 'organiser');
            });
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>  $resourceIds
     * @param  array<int, int>  $attendeeIds
     */
    public function update(Booking $booking, array $attributes, array $resourceIds, array $attendeeIds = []): Booking
    {
        if (! $booking->isEditable()) {
            throw new BookingException(__('Only pending, approved or confirmed bookings can be edited.'));
        }

        $start = Carbon::parse($attributes['starts_at']);
        $end = Carbon::parse($attributes['ends_at']);

        $resources = $this->resolveResources($resourceIds);

        return $this->withResourceLocks($resources->pluck('id')->all(), function () use ($booking, $attributes, $resources, $attendeeIds, $start, $end): Booking {
            $this->assertOccurrenceAvailable($resources, $start, $end, $booking->id);

            return DB::transaction(function () use ($booking, $attributes, $resources, $attendeeIds, $start, $end): Booking {
                $booking->update([
                    'title' => $attributes['title'],
                    'purpose' => $attributes['purpose'] ?? null,
                    'department' => $attributes['department'] ?? null,
                    'starts_at' => $start,
                    'ends_at' => $end,
                ]);

                $booking->resources()->detach();
                $this->attachResources($booking, $resources);

                $booking->attendees()->delete();
                $this->attachAttendees($booking, $booking->organiser, $attendeeIds);

                $requiresApproval = $resources->contains(fn (Resource $resource): bool => $resource->requiresApproval());

                if ($requiresApproval && $booking->status === BookingStatus::Confirmed) {
                    $booking->status = BookingStatus::Pending;
                    $booking->approved_at = null;
                    $booking->approved_by = null;
                    $booking->save();
                }

                $booking->refresh()->load('resources', 'attendees', 'organiser');

                return $booking;
            });
        });
    }

    /**
     * Approve one resource line or the whole booking.
     */
    public function approve(Booking $booking, User $approver, ?int $resourceId = null, ?string $note = null): Booking
    {
        if (! $booking->status->isActive()) {
            throw new BookingException(__('This booking can no longer be approved.'));
        }

        return DB::transaction(function () use ($booking, $approver, $resourceId, $note): Booking {
            $booking->load('resources');

            $targets = $booking->resources->filter(
                function (Resource $resource) use ($resourceId, $approver): bool {
                    if ($resource->pivot->status !== 'pending') {
                        return false;
                    }

                    if ($resourceId !== null && $resource->id !== $resourceId) {
                        return false;
                    }

                    if ($approver->isAdmin()) {
                        return true;
                    }

                    return $resource->manager_id === $approver->id;
                }
            );

            foreach ($targets as $resource) {
                $booking->resources()->updateExistingPivot($resource->id, [
                    'status' => 'approved',
                    'responded_by' => $approver->id,
                    'responded_at' => now(),
                    'note' => $note,
                ]);
            }

            $booking->refresh()->load('resources');

            if ($booking->isFullyApproved()) {
                $booking->status = BookingStatus::Confirmed;
                $booking->approved_at = now();
                $booking->approved_by = $approver->id;
                $booking->decision_note = $note;
                $booking->save();
            }

            $booking->load('organiser', 'resources');
            $this->notifyOrganiser($booking, 'approved');

            return $booking;
        });
    }

    /**
     * Reject a booking. Rejecting any resource rejects the whole booking.
     */
    public function reject(Booking $booking, User $approver, ?string $note = null): Booking
    {
        if (! $booking->status->isActive()) {
            throw new BookingException(__('This booking can no longer be rejected.'));
        }

        return DB::transaction(function () use ($booking, $approver, $note): Booking {
            $booking->resources()->newPivotStatement()
                ->where('booking_id', $booking->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'responded_by' => $approver->id,
                    'responded_at' => now(),
                    'note' => $note,
                    'updated_at' => now(),
                ]);

            $booking->status = BookingStatus::Rejected;
            $booking->rejected_at = now();
            $booking->rejected_by = $approver->id;
            $booking->decision_note = $note;
            $booking->save();

            $booking->load('organiser', 'resources');
            $this->notifyOrganiser($booking, 'rejected');

            return $booking;
        });
    }

    public function cancel(Booking $booking, User $user, ?string $reason = null): Booking
    {
        if (! $booking->status->isCancellable()) {
            throw new BookingException(__('This booking cannot be cancelled.'));
        }

        $noticeHours = (int) Setting::get('cancellation_notice_hours', 0);

        if (! $user->isAdmin()
            && $noticeHours > 0
            && $booking->status === BookingStatus::Confirmed
            && $booking->starts_at->isFuture()
            && $booking->starts_at->lt(now()->addHours($noticeHours))) {
            throw new BookingException(__('Bookings must be cancelled at least :hours hours in advance.', [
                'hours' => $noticeHours,
            ]));
        }

        return DB::transaction(function () use ($booking, $user, $reason): Booking {
            $targets = $booking->occurrences()->get()->prepend($booking)
                ->filter(fn (Booking $item): bool => $item->status->isCancellable());

            foreach ($targets as $target) {
                $target->status = BookingStatus::Cancelled;
                $target->cancelled_at = now();
                $target->cancelled_by = $user->id;
                $target->cancel_reason = $reason;
                $target->save();
            }

            $booking->load('organiser', 'resources', 'attendees.user');

            $recipients = $booking->attendees->pluck('user')->filter();
            Notification::send($recipients, new BookingCancelled($booking->fresh('organiser', 'resources')));

            return $booking;
        });
    }

    public function complete(Booking $booking, ?User $user = null): Booking
    {
        $this->transitions->assertCanTransition($booking->status, BookingStatus::Completed);

        $booking->status = BookingStatus::Completed;
        $booking->completed_at = now();
        $booking->save();

        return $booking;
    }

    public function markNoShow(Booking $booking, ?User $user = null): Booking
    {
        $this->transitions->assertCanTransition($booking->status, BookingStatus::NoShow);

        $booking->status = BookingStatus::NoShow;
        $booking->no_show_at = now();
        $booking->save();

        return $booking;
    }

    /**
     * @param  array<int, int>  $resourceIds
     * @return Collection<int, resource>
     */
    /**
     * Serialize the availability check and write for a set of resources.
     *
     * Two concurrent requests that touch the same resource must not both pass
     * the conflict check and then insert. We take one cache lock per resource
     * (in a stable order to avoid deadlocks) and hold it until the wrapped
     * transaction has committed. The check runs *inside* the lock so MySQL/
     * MariaDB cannot double-book the way an unlocked TOCTOU window would.
     *
     * @param  array<int, int>  $resourceIds
     */
    protected function withResourceLocks(array $resourceIds, Closure $callback): mixed
    {
        $ids = array_values(array_unique(array_map('intval', $resourceIds)));
        sort($ids);

        $locks = array_map(fn (int $id) => Cache::lock('booking-resource-'.$id, 10), $ids);
        $acquired = [];

        try {
            foreach ($locks as $lock) {
                $lock->block($this->lockWaitSeconds);
                $acquired[] = $lock;
            }

            return $callback();
        } finally {
            foreach (array_reverse($acquired) as $lock) {
                optional($lock)->release();
            }
        }
    }

    protected function resolveResources(array $resourceIds): Collection
    {
        $resources = Resource::query()->with('manager')->whereIn('id', $resourceIds)->get();

        if ($resources->isEmpty()) {
            throw new BookingException(__('Select at least one resource to book.'));
        }

        if ($resources->contains(fn (Resource $resource): bool => ! $resource->isBookable())) {
            throw new BookingException(__('One or more selected resources are not available for booking.'));
        }

        return $resources;
    }

    /**
     * @param  array<string, mixed>|null  $recurrence
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    protected function buildOccurrences(Carbon $start, Carbon $end, ?array $recurrence): array
    {
        if (! $recurrence || ($recurrence['frequency'] ?? 'none') === 'none') {
            return [['start' => $start, 'end' => $end]];
        }

        $occurrences = $this->recurrence->occurrences($start, $end, $recurrence);

        if ($occurrences === []) {
            throw new BookingException(__('The recurrence pattern did not produce any occurrences.'));
        }

        return $occurrences;
    }

    /**
     * @param  Collection<int, resource>  $resources
     * @param  array<int, array{start: Carbon, end: Carbon}>  $occurrences
     */
    protected function assertWindowIsAvailable(Collection $resources, array $occurrences): void
    {
        foreach ($resources as $resource) {
            foreach ($occurrences as $occurrence) {
                $this->availability->assertWithinRules($resource, $occurrence['start'], $occurrence['end']);
            }
        }

        $conflicts = collect($resources)->flatMap(function (Resource $resource) use ($occurrences): Collection {
            $found = collect();

            foreach ($occurrences as $occurrence) {
                try {
                    $this->availability->assertAvailable($resource, $occurrence['start'], $occurrence['end']);
                } catch (BookingConflictException $exception) {
                    $found->push($exception);
                }
            }

            return $found;
        });

        if ($conflicts->isNotEmpty()) {
            throw $conflicts->first();
        }
    }

    /**
     * @param  Collection<int, resource>  $resources
     */
    protected function assertOccurrenceAvailable(Collection $resources, Carbon $start, Carbon $end, ?int $ignoreBookingId): void
    {
        foreach ($resources as $resource) {
            $this->availability->assertWithinRules($resource, $start, $end, $ignoreBookingId);
            $this->availability->assertAvailable($resource, $start, $end, $ignoreBookingId);
        }
    }

    /**
     * @param  Collection<int, resource>  $resources
     */
    protected function attachResources(Booking $booking, Collection $resources): void
    {
        $booking->resources()->sync(
            $resources->mapWithKeys(fn (Resource $resource): array => [
                $resource->id => [
                    'status' => $resource->approval_mode === ApprovalMode::None ? 'not_required' : 'pending',
                ],
            ])->all()
        );
    }

    /**
     * @param  array<int, int>  $attendeeIds
     */
    protected function attachAttendees(Booking $booking, User $organiser, array $attendeeIds): void
    {
        $booking->attendees()->create([
            'user_id' => $organiser->id,
            'name' => $organiser->name,
            'email' => $organiser->email,
            'is_organiser' => true,
        ]);

        $others = User::query()
            ->whereIn('id', array_diff($attendeeIds, [$organiser->id]))
            ->get();

        foreach ($others as $user) {
            $booking->attendees()->create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_organiser' => false,
            ]);
        }
    }

    protected function notifyCreated(Booking $booking): void
    {
        $recipients = $booking->attendees->pluck('user')->filter();

        if ($recipients->isEmpty()) {
            $recipients = collect([$booking->organiser]);
        }

        Notification::send($recipients, new BookingCreated($booking));
    }

    protected function notifyApprovers(Booking $booking): void
    {
        $approvers = $booking->resources
            ->flatMap(function (Resource $resource): Collection {
                if ($resource->approval_mode === ApprovalMode::Admin) {
                    return User::query()->where('role', Role::Admin->value)->get();
                }

                return collect([$resource->manager])->filter();
            })
            ->unique('id')
            ->values();

        if ($approvers->isNotEmpty()) {
            Notification::send($approvers, new BookingApprovalRequired($booking));
        }
    }

    protected function notifyOrganiser(Booking $booking, string $decision): void
    {
        if ($booking->organiser) {
            $booking->organiser->notify(new BookingDecision($booking, $decision));
        }
    }
}
