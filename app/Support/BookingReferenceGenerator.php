<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Generates sequential, human-friendly booking references (e.g. BK-000123).
 */
class BookingReferenceGenerator
{
    public const SEQUENCE_KEY = 'booking_reference_sequence';

    public function generate(): string
    {
        return DB::transaction(function (): string {
            $prefix = (string) Setting::get('booking_reference_prefix', 'BK');

            $setting = Setting::query()
                ->where('key', self::SEQUENCE_KEY)
                ->lockForUpdate()
                ->first();

            $next = $setting ? ((int) $setting->value) + 1 : 1;

            do {
                $reference = sprintf('%s-%06d', $prefix, $next);
                $next++;
            } while (Booking::where('reference', $reference)->exists());

            $sequence = $next - 1;

            if ($setting) {
                $setting->update(['value' => (string) $sequence, 'type' => 'integer']);
            } else {
                Setting::create([
                    'key' => self::SEQUENCE_KEY,
                    'value' => (string) $sequence,
                    'type' => 'integer',
                ]);
            }

            Setting::flushCache();

            return $reference;
        });
    }
}
