<?php

namespace Database\Seeders;

use App\Enums\ApprovalMode;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@bookingkom.test')->first();
        $facilities = User::where('email', 'facilities@bookingkom.test')->first();
        $fleet = User::where('email', 'fleet@bookingkom.test')->first();

        $types = ResourceType::pluck('id', 'name');

        $resources = [
            ['Meeting Room 3', 'Meeting Room', 'HQ — Level 2', 12, ApprovalMode::None, $facilities],
            ['Board Room', 'Meeting Room', 'HQ — Level 3', 20, ApprovalMode::Owner, $facilities],
            ['Training Room A', 'Training Room', 'HQ — Level 1', 30, ApprovalMode::Admin, $facilities],
            ['Projector #12', 'Equipment', 'IT Store', null, ApprovalMode::None, $admin],
            ['Portable PA System', 'Equipment', 'IT Store', null, ApprovalMode::Owner, $admin],
            ['Company Vehicle #08', 'Company Vehicle', 'Basement car park', 7, ApprovalMode::Owner, $fleet],
            ['Van — Toyota Hiace', 'Company Vehicle', 'Basement car park', 12, ApprovalMode::Owner, $fleet],
            ['Multi-purpose Hall', 'Facility', 'Annex Building', 120, ApprovalMode::Admin, $facilities],
        ];

        foreach ($resources as $index => [$name, $type, $location, $capacity, $approval, $manager]) {
            Resource::updateOrCreate(
                ['name' => $name],
                [
                    'resource_type_id' => $types[$type] ?? null,
                    'code' => sprintf('RES-%04d', $index + 1),
                    'description' => 'Demo resource seeded for local development.',
                    'location' => $location,
                    'capacity' => $capacity,
                    'approval_mode' => $approval,
                    'manager_id' => $manager?->id,
                    'booking_rules' => [
                        'min_duration_minutes' => 15,
                        'max_duration_minutes' => 480,
                        'buffer_minutes' => $type === 'Company Vehicle' ? 30 : 0,
                    ],
                    'available_days' => [1, 2, 3, 4, 5],
                    'available_from' => '08:00',
                    'available_to' => '18:00',
                    'is_bookable' => true,
                    'created_by' => $admin?->id,
                ],
            );
        }

        // A person resource example (IT support bookable by the hour).
        Resource::updateOrCreate(
            ['name' => 'Ahmad (IT Support)'],
            [
                'resource_type_id' => $types['Person'] ?? null,
                'code' => 'RES-0009',
                'description' => 'Book IT support time.',
                'location' => 'IT Department',
                'approval_mode' => ApprovalMode::Accept,
                'manager_id' => $admin?->id,
                'available_days' => [1, 2, 3, 4, 5],
                'available_from' => '09:00',
                'available_to' => '17:00',
                'is_bookable' => true,
                'created_by' => $admin?->id,
            ],
        );
    }
}
