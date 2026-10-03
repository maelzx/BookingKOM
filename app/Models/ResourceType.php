<?php

namespace App\Models;

use Database\Factories\ResourceTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description', 'icon', 'color', 'requires_approval', 'sort_order'])]
class ResourceType extends Model
{
    /** @use HasFactory<ResourceTypeFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $type): void {
            if (blank($type->slug)) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_approval' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<resource, $this>
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
