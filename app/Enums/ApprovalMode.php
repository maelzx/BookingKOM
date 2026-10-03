<?php

namespace App\Enums;

enum ApprovalMode: string
{
    case None = 'none';
    case Admin = 'admin';
    case Owner = 'owner';
    case Accept = 'accept';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No approval required',
            self::Admin => 'Administrator approval',
            self::Owner => 'Resource owner approval',
            self::Accept => 'Resource must accept',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::None => 'Bookings are confirmed instantly.',
            self::Admin => 'Any administrator can approve bookings.',
            self::Owner => 'The assigned resource owner/manager approves bookings.',
            self::Accept => 'The resource owner must explicitly accept each booking.',
        };
    }

    public function requiresApproval(): bool
    {
        return $this !== self::None;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
