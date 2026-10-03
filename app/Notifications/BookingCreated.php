<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class BookingCreated extends Notification implements ShouldQueue
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
            ->subject(__('Booking :reference created', ['reference' => $this->booking->reference]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('A booking has been created.'))
            ->line('**'.$this->booking->title.'**')
            ->line(__('When: :range', ['range' => $this->range()]))
            ->line(__('Resources: :resources', ['resources' => $this->resources()]))
            ->line(__('Status: :status', ['status' => $this->booking->status->label()]))
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
            'status' => $this->booking->status->value,
            'starts_at' => $this->booking->starts_at->toIso8601String(),
            'url' => '/bookings/'.$this->booking->id,
        ];
    }

    protected function range(): string
    {
        return $this->booking->starts_at->format('d M Y H:i').' – '.$this->booking->ends_at->format('H:i');
    }

    protected function resources(): string
    {
        return $this->booking->resources->pluck('name')->join(', ');
    }
}
