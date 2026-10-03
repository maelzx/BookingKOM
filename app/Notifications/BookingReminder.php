<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class BookingReminder extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Reminder: :title', ['title' => $this->booking->title]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('This booking starts soon.'))
            ->line('**'.$this->booking->title.'**')
            ->line(__('When: :range', [
                'range' => $this->booking->starts_at->format('d M Y H:i').' – '.$this->booking->ends_at->format('H:i'),
            ]))
            ->line(__('Resources: :resources', ['resources' => $this->booking->resources->pluck('name')->join(', ')]))
            ->action(__('View booking'), url('/bookings/'.$this->booking->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'reference' => $this->booking->reference,
            'title' => $this->booking->title,
            'type' => 'reminder',
            'starts_at' => $this->booking->starts_at->toIso8601String(),
            'url' => '/bookings/'.$this->booking->id,
        ];
    }
}
