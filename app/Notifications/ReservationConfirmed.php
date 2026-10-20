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

        $mail = (new MailMessage)
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
            ->line('Receipt number: '.($receipt?->receipt_number ?? 'Processing'));

        // MailMessage renders a single action button, and calling action() a
        // second time silently discards the first. The button therefore points
        // at the receipt when one exists and falls back to the reservation page.
        if ($receipt !== null) {
            $mail->action('View receipt online', route('customer.receipts.show', $receipt));

            // The PDF is also attached. A link alone forces the customer to
            // open a browser, sign in, and keep the deployment URL stable;
            // the attachment carries the receipt wherever the email is read.
            // toMail() runs when the queued job is processed, so the document
            // is rendered from committed data at send time rather than being
            // frozen into the queued payload.
            $generator = app(GenerateReceiptPdf::class);

            $mail->attachData(
                $generator->render($receipt)->output(),
                $generator->filename($receipt),
                ['mime' => 'application/pdf'],
            );
        } else {
            $mail->action('View reservation', route('customer.reservations.show', $reservation));
        }

        return $mail
            ->line('Keep this email as proof of your confirmed reservation.')
            ->line('You can download this receipt again any time from your reservation history.');
    }
}
