<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Creates one pending reservation per payment method so both payment paths can
 * be demonstrated immediately after `migrate:fresh --seed`.
 *
 * Deadlines are set well into the future on purpose. The expiration sweeper runs
 * every minute and would expire a short deadline before the demo, which looks
 * like a bug even though it is correct behavior.
 */
class DemoPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $timezone = config('reservation.timezone');

        $customer = User::query()->where('email', 'maria@example.com')->firstOrFail();
        $courts = Court::query()->active()->orderBy('name')->get();

        // Day +1 and +2, skipping the facility's closed day.
        $openDay = $this->nextOpenDay($timezone);
        $secondDay = $this->nextOpenDay($timezone, 2);

        $this->seedPending($customer, $courts[0], $openDay, '19:00', PaymentMethod::Online);
        $this->seedPending($customer, $courts[1], $secondDay, '19:00', PaymentMethod::OnSite);

        $this->command?->info('Demo payment scenarios ready.');
    }

    private function seedPending(
        User $customer,
        Court $court,
        Carbon $date,
        string $time,
        PaymentMethod $method,
    ): void {
        $startsAt = $date->copy()->setTimeFromTimeString($time);
        $endsAt = $startsAt->copy()->addHours(2);
        $rate = (float) $court->hourly_rate;

        // Never create something the exclusion constraint would reject.
        if ($this->overlaps($court->id, $startsAt, $endsAt)) {
            return;
        }

        $reference = sprintf('RSRV-DEMO-%s', $method->value);

        if (Reservation::where('reference_number', $reference)->exists()) {
            return;
        }

        $reservation = Reservation::create([
            'reference_number' => $reference,
            'user_id' => $customer->id,
            'court_id' => $court->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'hourly_rate' => $rate,
            'total_amount' => number_format($rate * 2, 2, '.', ''),
            'status' => ReservationStatus::Pending,
            'payment_method' => $method,
            // Far enough out that the sweeper will not expire it mid-demo.
            'payment_deadline' => now()->addDays(3),
        ]);

        Payment::create([
            'reservation_id' => $reservation->id,
            'method' => $method,
            'provider' => $method === PaymentMethod::Online ? 'fake' : null,
            'amount' => $reservation->total_amount,
            'currency' => config('reservation.currency'),
            'status' => PaymentStatus::Pending,
        ]);
    }

    /**
     * The next open day at least `$minDaysOut` from today.
     */
    private function nextOpenDay(string $timezone, int $minDaysOut = 1): Carbon
    {
        $date = Carbon::now($timezone)->startOfDay()->addDays($minDaysOut);

        while (in_array($date->dayOfWeek, (array) config('reservation.closed_days'), true)) {
            $date = $date->addDay();
        }

        return $date;
    }

    private function overlaps(int $courtId, Carbon $startsAt, Carbon $endsAt): bool
    {
        return Reservation::query()
            ->where('court_id', $courtId)
            ->whereIn('status', [
                ReservationStatus::Pending->value,
                ReservationStatus::Confirmed->value,
                ReservationStatus::Completed->value,
            ])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();
    }
}
