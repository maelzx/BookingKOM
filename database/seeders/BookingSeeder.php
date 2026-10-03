<?php

namespace Database\Seeders;

use App\Enums\ApprovalMode;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $resources = Resource::all();
        $users = User::whereIn('role', ['user', 'resource_manager'])->get();

        if ($resources->isEmpty() || $users->isEmpty()) {
            return;
        }

        $samples = [
            ['Client Presentation', 0, 10, BookingStatus::Confirmed, 1],
            ['Weekly Team Sync', 2, 9, BookingStatus::Confirmed, 0],
            ['IT Onboarding — New Hire', 4, 14, BookingStatus::Pending, 1],
            ['Site Visit — Port Klang', 5, 8, BookingStatus::Confirmed, 1],
            ['Board Quarterly Review', 1, 15, BookingStatus::Pending, 0],
            ['Product Training', 2, 11, BookingStatus::Completed, 1],
            ['Warehouse Equipment Pickup', 6, 13, BookingStatus::Cancelled, 1],
            ['Marketing Planning', 0, 16, BookingStatus::NoShow, 1],
        ];

        foreach ($samples as $index => [$title, $resourceIndex, $hour, $status, $offsetDays]) {
            $resource = $resources[$resourceIndex % $resources->count()];
            $organiser = $users[$index % $users->count()];

            $start = Carbon::today()->addDays($offsetDays)->setTime($hour, 0);
            $end = (clone $start)->addHour();

            $booking = Booking::updateOrCreate(
                ['reference' => sprintf('BK-%06d', $index + 1)],
                [
                    'title' => $title,
                    'purpose' => 'Seeded demo booking.',
                    'user_id' => $organiser->id,
                    'department' => ['Operations', 'Sales', 'IT', 'Finance'][$index % 4],
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'status' => $status,
                    'approved_at' => $status->isActive() ? now() : null,
                    'approved_by' => $status->isActive() ? $organiser->id : null,
                    'completed_at' => $status === BookingStatus::Completed ? $end : null,
                    'cancelled_at' => $status === BookingStatus::Cancelled ? now() : null,
                    'cancelled_by' => $status === BookingStatus::Cancelled ? $organiser->id : null,
                    'no_show_at' => $status === BookingStatus::NoShow ? $start : null,
                ],
            );

            $booking->resources()->sync([
                $resource->id => [
                    'status' => $resource->approval_mode === ApprovalMode::None
                        ? 'not_required'
                        : ($status === BookingStatus::Pending ? 'pending' : 'approved'),
                ],
            ]);

            $booking->attendees()->updateOrCreate(
                ['user_id' => $organiser->id],
                ['name' => $organiser->name, 'email' => $organiser->email, 'is_organiser' => true],
            );
        }
    }
}
