<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Reservation;
use App\Services\Availability\AvailabilityService;
use Illuminate\Console\Command;

class ExpireReservationHolds extends Command
{
    protected $signature = 'reservations:expire';

    protected $description = 'Expire unpaid reservations whose payment deadline has passed';

    public function handle(AvailabilityService $availability): int
    {
        $overdue = Reservation::query()
            ->overdue()
            ->pluck('reference_number')
            ->all();

        $count = $availability->expireStaleHolds();

        if ($count === 0) {
            $this->info('No expired holds found.');

            return self::SUCCESS;
        }

        AuditLog::create([
            'action' => 'reservation.expired',
            'entity_type' => (new Reservation)->getMorphClass(),
            'details' => ['count' => $count, 'references' => $overdue],
        ]);

        $this->info("Expired {$count} unpaid reservation(s).");

        return self::SUCCESS;
    }
}
