<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Marks a payment settled and confirms its reservation in one transaction.
 *
 * Used by the webhook handler and by administrator on-site verification so
 * both paths produce identical state. Exactly one receipt is issued per
 * payment, and the event is recorded so a repeated callback is a no-op.
 */
class ConfirmPayment
{
    /**
     * @param  array<string, mixed>  $metadata  Safe provider fields to keep, such as the event id.
     * @return array{reservation: Reservation, receipt: Receipt|null, duplicate: bool}
     */
    public function handle(
        Payment $payment,
        ?User $verifiedBy = null,
        array $metadata = [],
    ): array {
        return DB::transaction(function () use ($payment, $verifiedBy, $metadata): array {
            $payment->refresh();
            $reservation = $payment->reservation()->firstOrFail();

            // A repeated callback must never create a second confirmation.
            if ($payment->status === PaymentStatus::Paid && $reservation->status !== ReservationStatus::Pending) {
                return [
                    'reservation' => $reservation,
                    'receipt' => $reservation->receipt,
                    'duplicate' => true,
                ];
            }

            // A late payment must not reclaim a slot that already expired and
            // may have been rebooked. It is recorded for manual reconciliation.
            if (! $reservation->status->acceptsPayment()) {
                $payment->update([
                    'status' => PaymentStatus::RequiresReview,
                    'paid_at' => now(),
                    'provider_metadata' => array_merge($payment->provider_metadata ?? [], $metadata),
                ]);

                AuditLog::create([
                    'user_id' => $verifiedBy?->id,
                    'action' => 'payment.requires_review',
                    'entity_type' => $payment->getMorphClass(),
                    'entity_id' => $payment->id,
                    'details' => [
                        'reservation' => $reservation->reference_number,
                        'reservation_status' => $reservation->status->value,
                        'reason' => 'Payment verified after the reservation left the pending state.',
                    ],
                ]);

                return [
                    'reservation' => $reservation,
                    'receipt' => $reservation->receipt,
                    'duplicate' => false,
                ];
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'verified_by' => $verifiedBy?->id ?? $payment->verified_by,
                'provider_metadata' => array_merge($payment->provider_metadata ?? [], $metadata),
            ]);

            $reservation->update(['status' => ReservationStatus::Confirmed]);

            $receipt = $this->issueReceipt($reservation, $payment);

            AuditLog::create([
                'user_id' => $verifiedBy?->id,
                'action' => 'payment.verified',
                'entity_type' => $payment->getMorphClass(),
                'entity_id' => $payment->id,
                'details' => [
                    'reservation' => $reservation->reference_number,
                    'method' => $payment->method->value,
                    'amount' => $payment->amount,
                ],
            ]);

            return [
                'reservation' => $reservation->refresh(),
                'receipt' => $receipt,
                'duplicate' => false,
            ];
        }, 3);
    }

    /**
     * Create the immutable receipt snapshot. Exactly one per payment.
     */
    private function issueReceipt(Reservation $reservation, Payment $payment): Receipt
    {
        if ($receipt = $reservation->receipt()->first()) {
            return $receipt;
        }

        $reservation->loadMissing('court', 'user');

        $startsAt = $reservation->starts_at->setTimezone(config('reservation.timezone'));

        return Receipt::create([
            'receipt_number' => $this->uniqueReceiptNumber(),
            'reservation_id' => $reservation->id,
            'payment_id' => $payment->id,
            'reservation_reference' => $reservation->reference_number,
            'customer_name' => $reservation->user?->name ?? 'Customer',
            'customer_email' => $reservation->user?->email ?? '',
            'court_name' => $reservation->court?->name ?? '',
            'court_location' => $reservation->court?->location ?? '',
            'reservation_date' => $startsAt->toDateString(),
            'starts_at' => $reservation->starts_at,
            'ends_at' => $reservation->ends_at,
            'duration_minutes' => $reservation->starts_at->diffInMinutes($reservation->ends_at),
            'payment_method' => $payment->method->value,
            'payment_status' => $payment->status->value,
            'amount_paid' => $payment->amount,
            'currency' => $payment->currency,
            'transaction_date' => now(),
        ]);
    }

    private function uniqueReceiptNumber(): string
    {
        do {
            $number = config('payments.receipt_prefix').'-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
        } while (Receipt::where('receipt_number', $number)->exists());

        return $number;
    }
}
