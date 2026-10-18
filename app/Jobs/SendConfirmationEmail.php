<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Notifications\ReservationConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Sends the reservation confirmation and receipt by email.
 *
 * Dispatched only after the payment transaction commits. A delivery failure
 * must never reverse a confirmed reservation or payment, so this job retries
 * on its own and does not touch reservation state.
 */
class SendConfirmationEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Backoff in seconds between attempts.
     *
     * @var list<int>
     */
    public array $backoff = [30, 120, 600];

    public function __construct(public readonly int $reservationId) {}

    public function handle(): void
    {
        $reservation = Reservation::with(['court', 'user', 'receipt'])->find($this->reservationId);

        if ($reservation === null || $reservation->user === null) {
            Log::warning('Confirmation email skipped, reservation not found.', [
                'reservation_id' => $this->reservationId,
            ]);

            return;
        }

        $reservation->user->notify(new ReservationConfirmed($reservation));
    }

    /**
     * Give up quietly after the final attempt. The reservation stays confirmed.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Confirmation email failed permanently.', [
            'reservation_id' => $this->reservationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
