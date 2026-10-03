<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed demo accounts — local/testing only.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $users = [
            ['name' => 'System Administrator', 'email' => 'admin@bookingkom.test', 'role' => Role::Admin],
            ['name' => 'Facilities Manager', 'email' => 'facilities@bookingkom.test', 'role' => Role::ResourceManager],
            ['name' => 'Fleet Manager', 'email' => 'fleet@bookingkom.test', 'role' => Role::ResourceManager],
            ['name' => 'Aisyah Rahman', 'email' => 'aisyah@bookingkom.test', 'role' => Role::User],
            ['name' => 'Tan Wei Ming', 'email' => 'weiming@bookingkom.test', 'role' => Role::User],
            ['name' => 'Arun Kumar', 'email' => 'arun@bookingkom.test', 'role' => Role::User],
            ['name' => 'Siti Aminah', 'email' => 'siti@bookingkom.test', 'role' => Role::User],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'email_verified_at' => now(),
                    'password' => 'password',
                ],
            );
        }
    }
}
