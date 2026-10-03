<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class BookingDecision extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking, public string $decision) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->decision === 'approved';

        return (new MailMessage)
            ->subject(__('Booking :decision: :reference', [
                'decision' => $approved ? __('approved') : __('rejected'),
                'reference' => $this->booking->reference,
            ]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line($approved
                ? __('Your booking has been approved.')
                : __('Your booking was not approved.'))
            ->line('**'.$this->booking->title.'**')
            ->when($this->booking->decision_note, fn (MailMessage $mail) => $mail->line(__('Note: :note', ['note' => $this->booking->decision_note])))
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
            'decision' => $this->decision,
            'url' => '/bookings/'.$this->booking->id,
        ];
    }
}
