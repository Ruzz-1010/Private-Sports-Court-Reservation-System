<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Models\AuditLog;
use App\Models\Reservation;
use App\Notifications\ReservationReminder;
use Illuminate\Console\Command;

/**
 * Emails a reminder shortly before a confirmed reservation starts.
 *
 * Runs every minute from the scheduler. A send is recorded in the audit log,
 * so a booking is never reminded twice even though the command keeps running.
 * The reminder is skipped for cancelled, expired, or already finished slots.
 */
class SendReservationReminders extends Command
{
    protected $signature = 'reservations:remind';

    protected $description = 'Email a reminder before a confirmed reservation starts';

    private const ACTION = 'reservation.reminder_sent';

    public function handle(): int
    {
        $minutes = (int) config('reservation.reminder.minutes_before', 10);

        if ($minutes <= 0) {
            $this->info('Reminders are switched off.');

            return self::SUCCESS;
        }

        $windowEnd = now()->addMinutes($minutes);

        $candidates = Reservation::query()
            ->with(['user', 'court'])
            ->where('status', ReservationStatus::Confirmed->value)
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', $windowEnd)
            ->orderBy('starts_at')
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No reminders are due.');

            return self::SUCCESS;
        }

        // One query for the bookings already reminded, so a run that overlaps
        // with a previous one cannot email the same customer twice.
        $alreadySent = AuditLog::query()
            ->where('action', self::ACTION)
            ->where('entity_type', (new Reservation)->getMorphClass())
            ->whereIn('entity_id', $candidates->modelKeys())
            ->pluck('entity_id')
            ->all();

        $sent = 0;
        $failed = 0;

        foreach ($candidates as $reservation) {
            if (in_array($reservation->id, $alreadySent, true)) {
                continue;
            }

            $customer = $reservation->user;

            if ($customer === null) {
                continue;
            }

            try {
                $customer->notify(new ReservationReminder($reservation));
            } catch (\Throwable $exception) {
                // A mail problem must not stop the other reminders.
                $failed++;
                report($exception);

                continue;
            }

            AuditLog::create([
                'action' => self::ACTION,
                'entity_type' => (new Reservation)->getMorphClass(),
                'entity_id' => $reservation->id,
                'details' => [
                    'reference' => $reservation->reference_number,
                    'email' => $customer->email,
                    'minutes_before' => $minutes,
                ],
            ]);

            $sent++;
        }

        $this->info("Sent {$sent} reminder(s).".($failed > 0 ? " {$failed} failed." : ''));

        return self::SUCCESS;
    }
}
