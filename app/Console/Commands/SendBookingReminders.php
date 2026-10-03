<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Setting;
use App\Notifications\BookingReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('bookings:remind {--force : Re-send reminders even if a booking was already reminded}')]
#[Description('Send reminders for upcoming confirmed bookings.')]
class SendBookingReminders extends Command
{
    public function handle(): int
    {
        if (! Setting::get('notifications_enabled', true)) {
            $this->info('Notifications are disabled.');

            return self::SUCCESS;
        }

        $lead = (int) Setting::get('reminder_lead_minutes', 60);

        $bookings = Booking::query()
            ->with(['resources', 'organiser', 'attendees.user'])
            ->where('status', BookingStatus::Confirmed->value)
            ->whereBetween('starts_at', [now(), now()->addMinutes($lead)])
            ->when(! $this->option('force'), fn ($query) => $query->whereNull('reminded_at'))
            ->get();

        $sent = 0;

        foreach ($bookings as $booking) {
            $recipients = $booking->attendees->pluck('user')->filter();

            if ($recipients->isEmpty() && $booking->organiser) {
                $recipients = collect([$booking->organiser]);
            }

            if ($recipients->isEmpty()) {
                continue;
            }

            Notification::send($recipients, new BookingReminder($booking));

            $booking->forceFill(['reminded_at' => now()])->save();

            $sent += $recipients->count();
        }

        $this->info("Sent {$sent} booking reminder(s).");

        return self::SUCCESS;
    }
}
