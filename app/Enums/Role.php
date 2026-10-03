<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case ResourceManager = 'resource_manager';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::ResourceManager => 'Resource Manager',
            self::User => 'User',
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
