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

        $timezone = config('reservation.timezone');
        $starts = $reservation->starts_at->setTimezone($timezone);
        $ends = $reservation->ends_at->setTimezone($timezone);

        // The subject, the single action button and the attached PDF are set on
        // the MailMessage itself; the body is a hand-built HTML view so the
        // receipt reads as a card instead of one more gray line in a list.
        $mail = (new MailMessage)
            ->subject("Reservation confirmed — {$reservation->reference_number}")
            ->view('emails.confirmed', [
                'subject' => "Reservation confirmed — {$reservation->reference_number}",
                'facilityName' => config('reservation.facility_name'),
                'facilityAddress' => config('reservation.facility_address'),
                'customerName' => $notifiable->name,
                'reference' => $reservation->reference_number,
                'receiptNumber' => $receipt?->receipt_number,
                'amount' => $total,
                'paymentMethod' => $paymentMethod,
                'hasPdf' => $receipt !== null,
                'details' => [
                    ['label' => 'Court', 'value' => $reservation->court?->name ?? 'Court'],
                    ['label' => 'Location', 'value' => $reservation->court?->location ?? '—'],
                    ['label' => 'Date', 'value' => $starts->format('l, F j, Y')],
                    ['label' => 'Time', 'value' => $starts->format('g:i A').' – '.$ends->format('g:i A')],
                    ['label' => 'Duration', 'value' => $this->durationLabel($starts, $ends)],
                    ['label' => 'Payment method', 'value' => $paidOnSite ? 'On-site (paid at the court)' : 'Online (PayMongo)'],
                ],
            ])
            ->action('View receipt online', $receipt !== null
                ? route('customer.receipts.show', $receipt)
                : route('customer.reservations.show', $reservation));

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

        return $mail;
    }

    /**
     * "1 hr", "1 hr 30 min", "2 hr" — friendlier than "90 minutes" for a court booking.
     */
    private function durationLabel(\DateTimeInterface $starts, \DateTimeInterface $ends): string
    {
        $minutes = (int) $starts->diff($ends)->i + ((int) $starts->diff($ends)->h * 60);

        if ($minutes < 60) {
            return $minutes.' min';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $hours.' hr'.($rest > 0 ? ' '.$rest.' min' : '');
    }
}
