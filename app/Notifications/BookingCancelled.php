<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class BookingCancelled extends Notification implements ShouldQueue
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
            ->subject(__('Booking cancelled: :reference', ['reference' => $this->booking->reference]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('A booking you are involved in has been cancelled.'))
            ->line('**'.$this->booking->title.'**')
            ->line(__('When: :range', [
                'range' => $this->booking->starts_at->format('d M Y H:i').' – '.$this->booking->ends_at->format('H:i'),
            ]))
            ->when($this->booking->cancel_reason, fn (MailMessage $mail) => $mail->line(__('Reason: :reason', ['reason' => $this->booking->cancel_reason])))
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
            'type' => 'cancelled',
            'url' => '/bookings/'.$this->booking->id,
        ];
    }
}
