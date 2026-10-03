<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Seed the default organisation settings.
     */
    public function run(): void
    {
        $defaults = [
            'org_name' => ['BookingKOM', 'string'],
            'org_timezone' => ['Asia/Kuala_Lumpur', 'string'],
            'working_days' => [[1, 2, 3, 4, 5], 'array'],
            'working_hours_start' => ['08:00', 'string'],
            'working_hours_end' => ['18:00', 'string'],
            'default_booking_minutes' => [60, 'integer'],
            'min_booking_minutes' => [15, 'integer'],
            'max_booking_minutes' => [480, 'integer'],
            'advance_booking_days' => [90, 'integer'],
            'min_notice_minutes' => [0, 'integer'],
            'cancellation_notice_hours' => [2, 'integer'],
            'require_approval_default' => [false, 'boolean'],
            'resource_code_prefix' => ['RES', 'string'],
            'resource_code_sequence' => [0, 'integer'],
            'booking_reference_prefix' => ['BK', 'string'],
            'booking_reference_sequence' => [0, 'integer'],
            'reminder_lead_minutes' => [60, 'integer'],
            'notifications_enabled' => [true, 'boolean'],
            'qr_enabled' => [true, 'boolean'],
        ];

        foreach ($defaults as $key => [$value, $type]) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $type === 'array' ? json_encode($value) : (string) $value,
                    'type' => $type,
                ],
            );
        }

        Setting::flushCache();
    }
}
