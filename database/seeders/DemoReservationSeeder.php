<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo data so dashboards, reports, and the presentation show real activity.
 *
 * Safe to run repeatedly: everything is keyed on a deterministic reference.
 */
class DemoReservationSeeder extends Seeder
{
    public function run(): void
    {
        $timezone = config('reservation.timezone');

        $customers = collect([
            ['email' => 'juan@example.com', 'name' => 'Juan Dela Cruz'],
            ['email' => 'maria@example.com', 'name' => 'Maria Santos'],
            ['email' => 'pedro@example.com', 'name' => 'Pedro Reyes'],
        ])->map(fn (array $data) => User::query()->firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => bcrypt('customer123'),
                'role' => Role::Customer,
                'email_verified_at' => now(),
            ],
        ));

        $courts = Court::query()->active()->orderBy('name')->get();
        $admin = User::query()->where('role', Role::Admin->value)->first()
            ?? User::query()->where('email', 'admin@court.local')->firstOrFail();

        $today = Carbon::now($timezone)->startOfDay();

        // A spread across today and the next few days.
        $plan = [
            ['day' => 0, 'start' => '09:00', 'hours' => 2, 'status' => ReservationStatus::Confirmed, 'paid' => true],
            ['day' => 0, 'start' => '14:00', 'hours' => 2, 'status' => ReservationStatus::Confirmed, 'paid' => true],
            ['day' => 0, 'start' => '17:00', 'hours' => 3, 'status' => ReservationStatus::Pending, 'paid' => false, 'method' => PaymentMethod::OnSite],
            ['day' => 1, 'start' => '10:00', 'hours' => 2, 'status' => ReservationStatus::Confirmed, 'paid' => true],
            ['day' => 1, 'start' => '15:00', 'hours' => 1, 'status' => ReservationStatus::Pending, 'paid' => false],
            ['day' => 2, 'start' => '08:00', 'hours' => 4, 'status' => ReservationStatus::Confirmed, 'paid' => true],
            ['day' => 3, 'start' => '16:00', 'hours' => 2, 'status' => ReservationStatus::Pending, 'paid' => false],
            ['day' => -2, 'start' => '11:00', 'hours' => 3, 'status' => ReservationStatus::Confirmed, 'paid' => true],
            ['day' => -1, 'start' => '13:00', 'hours' => 2, 'status' => ReservationStatus::Expired, 'paid' => false],
        ];

        $i = 0;

        foreach ($plan as $entry) {
            $court = $courts[$i % $courts->count()];
            $customer = $customers[$i % $customers->count()];

            $startsAt = $today->copy()
                ->addDays($entry['day'])
                ->setTimeFromTimeString($entry['start']);

            $endsAt = $startsAt->copy()->addHours($entry['hours']);
            $method = $entry['method'] ?? PaymentMethod::Online;
            $rate = (float) $court->hourly_rate;

            $reference = sprintf('RSRV-%s-%02d', $today->format('ymd'), $i + 1);

            if (Reservation::where('reference_number', $reference)->exists()) {
                $i++;

                continue;
            }

            // The exclusion constraint rejects any overlap, so skip instead of
            // forcing data the database would never allow.
            if ($this->overlaps($court->id, $startsAt, $endsAt)) {
                $i++;

                continue;
            }

            $reservation = Reservation::create([
                'reference_number' => $reference,
                'user_id' => $customer->id,
                'court_id' => $court->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'hourly_rate' => $rate,
                'total_amount' => number_format($rate * $entry['hours'], 2, '.', ''),
                'status' => $entry['status'],
                'payment_method' => $method,
                'payment_deadline' => $entry['status'] === ReservationStatus::Pending
                    ? now()->addMinutes((int) config('reservation.hold_minutes.online'))
                    : now()->addHours(2),
                'expiration_reason' => $entry['status'] === ReservationStatus::Expired
                    ? 'Payment deadline passed before payment was verified.'
                    : null,
            ]);

            Payment::create([
                'reservation_id' => $reservation->id,
                'method' => $method,
                'provider' => $method === PaymentMethod::Online ? 'maya' : null,
                'amount' => $reservation->total_amount,
                'currency' => config('reservation.currency'),
                'status' => $entry['paid'] ? PaymentStatus::Paid : PaymentStatus::Pending,
                'paid_at' => $entry['paid'] ? $reservation->created_at : null,
                'verified_by' => $entry['paid'] && $method === PaymentMethod::OnSite ? $admin->id : null,
            ]);

            if ($entry['paid']) {
                AuditLog::create([
                    'user_id' => $admin->id,
                    'action' => 'payment.verified',
                    'entity_type' => $reservation->getMorphClass(),
                    'entity_id' => $reservation->id,
                    'details' => ['reference' => $reference],
                ]);
            }

            $i++;
        }

        $this->command?->info('Demo reservations seeded.');
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
