<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent a short while before a confirmed reservation starts.
 *
 * This is the "see you at the court" nudge: it repeats the reference number
 * and the exact time so a customer who forgot can still show up. It is sent by
 * the reservations:remind command, never twice for the same booking (the
 * command records each send in the audit log).
 */
class ReservationReminder extends Notification
{
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
        $reservation = $this->reservation->loadMissing('court');
        $timezone = config('reservation.timezone');

        $starts = $reservation->starts_at->setTimezone($timezone);
        $ends = $reservation->ends_at->setTimezone($timezone);
        $minutes = (int) config('reservation.reminder.minutes_before', 10);
        $timeLeft = $minutes >= 60
            ? intdiv($minutes, 60).' hour'.(intdiv($minutes, 60) === 1 ? '' : 's')
            : $minutes.' minute'.($minutes === 1 ? '' : 's');

        return (new MailMessage)
            ->subject("Your court time is coming up — {$reservation->reference_number}")
            ->view('emails.reminder', [
                'subject' => "Your court time is coming up — {$reservation->reference_number}",
                'facilityName' => config('reservation.facility_name'),
                'facilityAddress' => config('reservation.facility_address'),
                'customerName' => $notifiable->name,
                'reference' => $reservation->reference_number,
                'timeLeft' => $timeLeft,
                'startsAt' => $starts->format('g:i A'),
                'details' => [
                    ['label' => 'Court', 'value' => $reservation->court?->name ?? 'Court'],
                    ['label' => 'Location', 'value' => $reservation->court?->location ?? '—'],
                    ['label' => 'Date', 'value' => $starts->format('l, F j, Y')],
                    ['label' => 'Time', 'value' => $starts->format('g:i A').' – '.$ends->format('g:i A')],
                    ['label' => 'Reference number', 'value' => $reservation->reference_number],
                ],
            ])
            ->action('View my reservation', route('customer.reservations.show', $reservation));
    }
}
