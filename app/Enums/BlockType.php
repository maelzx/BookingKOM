<?php

namespace App\Enums;

enum BlockType: string
{
    case Blocked = 'blocked';
    case Maintenance = 'maintenance';
    case Holiday = 'holiday';

    public function label(): string
    {
        return match ($this) {
            self::Blocked => 'Unavailable',
            self::Maintenance => 'Maintenance',
            self::Holiday => 'Public holiday',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Blocked => 'bg-gray-100 text-gray-800',
            self::Maintenance => 'bg-yellow-100 text-yellow-800',
            self::Holiday => 'bg-purple-100 text-purple-800',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
