<?php

namespace App\Notifications;

use App\Enums\PaymentMethod;
use App\Models\Reservation;
use App\Services\Receipts\GenerateReceiptPdf;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent the moment a reservation is created, before any payment is verified.
 *
 * This is the customer's proof that the booking reached the system. It carries
 * the reference number they present at the court, so it must never be delayed
 * by a queue worker. It is sent from inside the request and a mail failure is
 * caught by the caller so a booking is never lost because of email.
 */
class ReservationReceived extends Notification
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
        $total = number_format((float) $reservation->total_amount, 2);
        $onSite = $reservation->payment_method === PaymentMethod::OnSite;
        $deadline = $reservation->payment_deadline?->setTimezone($timezone)->format('l, F j, Y g:i A');

        $mail = (new MailMessage)
            ->subject("We received your reservation — {$reservation->reference_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line($onSite
                ? 'Your court reservation has been received. Your slot is held for you — just pay at the court on your booking day.'
                : 'Your court reservation has been received and your slot is being held while your payment is completed.')
            ->line('Court: '.($reservation->court?->name ?? 'Court')." ({$reservation->court?->location})")
            ->line('Date: '.$reservation->starts_at->setTimezone($timezone)->format('l, F j, Y'))
            ->line('Time: '.$reservation->starts_at->setTimezone($timezone)->format('g:i A')
                .' – '.$reservation->ends_at->setTimezone($timezone)->format('g:i A'))
            ->line("Amount to pay: PHP {$total}")
            ->line('Payment method: '.($onSite ? 'On-site (pay at the court)' : 'Online (PayMongo)'))
            ->line('Status: Awaiting payment')
            ->line("Reference number: {$reservation->reference_number}");

        if ($onSite) {
            $mail->line('Please keep this email. Show your reference number, or this email, at the court management desk when you arrive so your reservation can be checked.');
        } else {
            $mail->line($deadline !== null
                ? "Please finish your online payment before {$deadline}. After that, the slot is released for other customers."
                : 'Please finish your online payment to keep this slot.');
        }

        // Attach the same document the verified-payment email carries, marked
        // PENDING PAYMENT, so the customer has one printable slip at the court
        // from the moment they book.
        try {
            $generator = app(GenerateReceiptPdf::class);

            $mail->attachData(
                $generator->renderPending($reservation)->output(),
                $generator->pendingFilename($reservation),
                ['mime' => 'application/pdf'],
            );
        } catch (\Throwable $exception) {
            // A PDF problem must never stop the email that proves the booking.
            report($exception);
        }

        return $mail
            ->action('View my reservation', route('customer.reservations.show', $reservation))
            ->line('Your reservation slip is attached to this email as a PDF. Present it at the court management desk on your booking day.')
            ->line('A separate confirmation email with your official PDF receipt is sent once your payment is verified.');
    }
}
