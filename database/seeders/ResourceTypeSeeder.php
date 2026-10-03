<?php

namespace Database\Seeders;

use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class ResourceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Meeting Room', 'description' => 'Bookable meeting and conference rooms.'],
            ['name' => 'Company Vehicle', 'description' => 'Pool vehicles available for trips.'],
            ['name' => 'Equipment', 'description' => 'Projectors, cameras, audio and other equipment.'],
            ['name' => 'Training Room', 'description' => 'Rooms set up for workshops and training.'],
            ['name' => 'Facility', 'description' => 'Shared spaces such as halls and courts.'],
            ['name' => 'Person', 'description' => 'Staff whose time can be booked (e.g. IT support).'],
        ];

        foreach ($types as $index => $type) {
            ResourceType::updateOrCreate(
                ['slug' => str($type['name'])->slug()->toString()],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'sort_order' => $index,
                ],
            );
        }
    }
}
