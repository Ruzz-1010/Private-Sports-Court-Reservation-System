<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationConfirmed extends Notification
{
    use Queueable;

    public function __construct(public readonly Reservation $reservation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reservation = $this->reservation->loadMissing(['court', 'receipt']);
        $receipt = $reservation->receipt;
        $total = number_format((float) $reservation->total_amount, 2);
        $paymentMethod = ucfirst(str_replace('_', ' ', $receipt?->payment_method ?? 'online'));

        return (new MailMessage)
            ->subject("Reservation confirmed — {$reservation->reference_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line('Your payment was verified and your court reservation is confirmed.')
            ->line("Court: {$reservation->court?->name} ({$reservation->court?->location})")
            ->line('Date: '.$reservation->starts_at->setTimezone(config('reservation.timezone'))->format('l, F j, Y'))
            ->line('Time: '.$reservation->starts_at->setTimezone(config('reservation.timezone'))->format('g:i A')
                .' – '.$reservation->ends_at->setTimezone(config('reservation.timezone'))->format('g:i A'))
            ->line("Amount paid: PHP {$total}")
            ->line("Payment method: {$paymentMethod}")
            ->line('Payment status: Paid')
            ->line('Reference: '.$reservation->reference_number)
            ->line('Receipt number: '.($receipt?->receipt_number ?? 'Processing'))
            ->action('View reservation and receipt', route('customer.reservations.show', $reservation))
            ->line('Keep this email as proof of your confirmed reservation.');
    }
}
