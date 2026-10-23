<?php

namespace App\Notifications;

use App\Models\Reservation;
use App\Services\Receipts\GenerateReceiptPdf;
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
        $paidOnSite = ($receipt?->payment_method ?? '') === 'on_site';

        $mail = (new MailMessage)
            ->subject("Reservation confirmed — {$reservation->reference_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line($paidOnSite
                ? 'Your on-site payment was received and your court reservation is confirmed. Show this email, or the attached receipt, at the court when you arrive.'
                : 'Your payment was verified and your court reservation is confirmed.')
            ->line('Court: '.($reservation->court?->name ?? 'Court')." ({$reservation->court?->location})")
            ->line('Date: '.$reservation->starts_at->setTimezone(config('reservation.timezone'))->format('l, F j, Y'))
            ->line('Time: '.$reservation->starts_at->setTimezone(config('reservation.timezone'))->format('g:i A')
                .' – '.$reservation->ends_at->setTimezone(config('reservation.timezone'))->format('g:i A'))
            ->line("Amount paid: PHP {$total}")
            ->line("Payment method: {$paymentMethod}")
            ->line('Payment status: Paid')
            ->line('Reference: '.$reservation->reference_number)
            ->line('Receipt number: '.($receipt?->receipt_number ?? 'Processing'));

        if ($receipt !== null) {
            // Attach the actual PDF so the customer can keep the receipt
            // without needing to sign in or open the website.
            // toMail() runs when the queued job is processed, so the document
            // is rendered from committed data at send time rather than being
            // frozen into the queued payload.
            $generator = app(GenerateReceiptPdf::class);

            $mail->attachData(
                $generator->render($receipt)->output(),
                $generator->filename($receipt),
                ['mime' => 'application/pdf'],
            );
        }

        return $mail
            ->line($receipt !== null
                ? 'Your PDF receipt is attached to this email.'.($paidOnSite ? ' Present it at the court on your booking day.' : '')
                : 'Your receipt is being prepared and will be available from your reservation history.')
            ->action('View receipt online', $receipt !== null
                ? route('customer.receipts.show', $receipt)
                : route('customer.reservations.show', $reservation))
            ->line('Keep this email as proof of your confirmed reservation.');
    }
}
