<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Support\BookingReferenceGenerator;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'reference',
    'title',
    'purpose',
    'user_id',
    'department',
    'starts_at',
    'ends_at',
    'status',
    'recurrence_rule',
    'recurrence_parent_id',
    'occurrence_index',
    'approved_at',
    'approved_by',
    'rejected_at',
    'rejected_by',
    'decision_note',
    'cancelled_at',
    'cancelled_by',
    'cancel_reason',
    'completed_at',
    'no_show_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('booking');
    }

    protected static function booted(): void
    {
        static::creating(function (self $booking): void {
            if (blank($booking->reference)) {
                $booking->reference = app(BookingReferenceGenerator::class)->generate();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'recurrence_rule' => 'array',
            'occurrence_index' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'no_show_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function organiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recurrence_parent_id');
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(self::class, 'recurrence_parent_id')->orderBy('starts_at');
    }

    /**
     * @return BelongsToMany<resource, $this>
     */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class)
            ->withPivot(['status', 'responded_by', 'responded_at', 'note'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<BookingAttendee, $this>
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(BookingAttendee::class);
    }

    public function durationInMinutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }

    public function isRecurring(): bool
    {
        return $this->recurrence_parent_id !== null || ! empty($this->recurrence_rule);
    }

    public function isEditable(): bool
    {
        return $this->status->isActive();
    }

    public function canBeManagedBy(User $user): bool
    {
        return $user->isAdmin() || $this->user_id === $user->id;
    }

    /**
     * Resources whose per-resource approval is still outstanding.
     *
     * @return Collection<int, resource>
     */
    public function resourcesAwaitingResponse(): Collection
    {
        return $this->resources->filter(
            fn (Resource $resource): bool => $resource->pivot->status === 'pending'
        )->values();
    }

    public function isFullyApproved(): bool
    {
        return $this->resources->every(
            fn (Resource $resource): bool => in_array($resource->pivot->status, ['approved', 'not_required'], true)
        );
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [
            BookingStatus::Pending->value,
            BookingStatus::Approved->value,
            BookingStatus::Confirmed->value,
        ]);
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', BookingStatus::Pending->value);
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopeBooked(Builder $query): void
    {
        $query->whereIn('status', [
            BookingStatus::Pending->value,
            BookingStatus::Approved->value,
            BookingStatus::Confirmed->value,
            BookingStatus::Completed->value,
        ]);
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopeOverlapping(Builder $query, mixed $startsAt, mixed $endsAt): void
    {
        $query->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt);
    }
}
