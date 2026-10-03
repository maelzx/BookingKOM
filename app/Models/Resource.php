<?php

namespace App\Models;

use App\Enums\ApprovalMode;
use App\Enums\ResourceStatus;
use App\Support\ResourceCodeGenerator;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'resource_type_id',
    'name',
    'code',
    'description',
    'location',
    'status',
    'capacity',
    'image_path',
    'approval_mode',
    'manager_id',
    'booking_rules',
    'available_from',
    'available_to',
    'available_days',
    'is_bookable',
    'custom_fields',
    'created_by',
])]
class Resource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('resource');
    }

    protected static function booted(): void
    {
        static::creating(function (self $resource): void {
            if (blank($resource->code)) {
                $resource->code = app(ResourceCodeGenerator::class)->generate();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ResourceStatus::class,
            'approval_mode' => ApprovalMode::class,
            'booking_rules' => 'array',
            'available_days' => 'array',
            'custom_fields' => 'array',
            'capacity' => 'integer',
            'is_bookable' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ResourceType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class, 'resource_type_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ResourceBlockedPeriod, $this>
     */
    public function blockedPeriods(): HasMany
    {
        return $this->hasMany(ResourceBlockedPeriod::class);
    }

    /**
     * @return BelongsToMany<Booking, $this>
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class)
            ->withPivot(['status', 'responded_by', 'responded_at', 'note'])
            ->withTimestamps();
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @param  Builder<resource>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', ResourceStatus::Active->value);
    }

    /**
     * @param  Builder<resource>  $query
     */
    public function scopeBookable(Builder $query): void
    {
        $query->where('status', ResourceStatus::Active->value)->where('is_bookable', true);
    }

    public function isBookable(): bool
    {
        return $this->status->isBookable() && $this->is_bookable;
    }

    public function requiresApproval(): bool
    {
        return $this->approval_mode->requiresApproval();
    }

    public function rule(string $key, mixed $default = null): mixed
    {
        return data_get($this->booking_rules ?? [], $key, $default);
    }

    /**
     * Working window for a given date, honouring per-resource overrides and
     * falling back to the organisation settings.
     *
     * @return array{0: string, 1: string, 2: array<int, int>}|null
     */
    public function workingWindow(): ?array
    {
        $days = $this->available_days;

        if (! is_array($days) || $days === []) {
            $days = Setting::get('working_days', [1, 2, 3, 4, 5]);
        }

        $from = $this->available_from ?: (string) Setting::get('working_hours_start', '08:00');
        $to = $this->available_to ?: (string) Setting::get('working_hours_end', '18:00');

        return [$from, $to, array_map('intval', (array) $days)];
    }
}
