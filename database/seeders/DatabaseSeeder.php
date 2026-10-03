<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Settings are required in every environment.
        $this->call(SettingSeeder::class);

        // Demo accounts and sample data are local/testing only.
        if (app()->environment('production')) {
            return;
        }

        $this->call([
            UserSeeder::class,
            ResourceTypeSeeder::class,
            ResourceSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
