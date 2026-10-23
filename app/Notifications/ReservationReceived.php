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

        $starts = $reservation->starts_at->setTimezone($timezone);
        $ends = $reservation->ends_at->setTimezone($timezone);

        // The body is a hand-built HTML view so the reference number and the
        // amount read as a card instead of one more gray line in a list. The
        // subject, the single action button and the attached slip are still set
        // on the MailMessage itself.
        $instruction = $onSite
            ? 'Keep this email. Show your reference number, or the attached slip, at the court management desk when you arrive.'
            : ($deadline !== null
                ? "Finish your online payment before {$deadline}. After that the slot is released for other customers."
                : 'Finish your online payment to keep this slot.');

        $mail = (new MailMessage)
            ->subject("We received your reservation — {$reservation->reference_number}")
            ->view('emails.received', [
                'subject' => "We received your reservation — {$reservation->reference_number}",
                'facilityName' => config('reservation.facility_name'),
                'facilityAddress' => config('reservation.facility_address'),
                'customerName' => $notifiable->name,
                'reference' => $reservation->reference_number,
                'amount' => $total,
                'onSite' => $onSite,
                'hasPdf' => false,
                'statusLine' => $onSite
                    ? 'Your slot is held. Pay at the court on your booking day.'
                    : 'Your slot is held while your payment is completed.',
                'deadlineLine' => $onSite ? null : ($deadline !== null ? "Pay before {$deadline} to keep this slot." : null),
                'instruction' => $instruction,
                'details' => [
                    ['label' => 'Court', 'value' => $reservation->court?->name ?? 'Court'],
                    ['label' => 'Location', 'value' => $reservation->court?->location ?? '—'],
                    ['label' => 'Date', 'value' => $starts->format('l, F j, Y')],
                    ['label' => 'Time', 'value' => $starts->format('g:i A').' – '.$ends->format('g:i A')],
                    ['label' => 'Payment method', 'value' => $onSite ? 'On-site (pay at the court)' : 'Online (PayMongo)'],
                    ['label' => 'Status', 'value' => 'Awaiting payment'],
                ],
            ])
            ->action('View my reservation', route('customer.reservations.show', $reservation));

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

            // Re-render the view flag so the card can say the slip is attached.
            $mail->viewData['hasPdf'] = true;
        } catch (\Throwable $exception) {
            // A PDF problem must never stop the email that proves the booking.
            report($exception);
        }

        return $mail;
    }
}
