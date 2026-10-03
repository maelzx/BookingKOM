<?php

namespace App\Support;

use App\Models\Resource;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Generates sequential resource codes (e.g. RES-0001) used for labels and QR links.
 */
class ResourceCodeGenerator
{
    public const SEQUENCE_KEY = 'resource_code_sequence';

    public function generate(): string
    {
        return DB::transaction(function (): string {
            $prefix = (string) Setting::get('resource_code_prefix', 'RES');

            $setting = Setting::query()
                ->where('key', self::SEQUENCE_KEY)
                ->lockForUpdate()
                ->first();

            $next = $setting ? ((int) $setting->value) + 1 : 1;

            do {
                $code = sprintf('%s-%04d', $prefix, $next);
                $next++;
            } while (Resource::withTrashed()->where('code', $code)->exists());

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

            return $code;
        });
    }
}
