<?php

use App\Enums\ReservationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Database-level protection against double booking.
     *
     * Application validation alone cannot stop two simultaneous requests: both
     * can read "available" before either writes. This exclusion constraint makes
     * the second INSERT fail at the database instead.
     *
     * The interval is half-open `[starts_at, ends_at)`, so a reservation ending
     * at 11:00 does not conflict with one starting at 11:00.
     *
     * Note on expired holds: the predicate cannot include
     * `payment_deadline > now()` because PostgreSQL requires an IMMUTABLE
     * predicate and `now()` is only STABLE. An unpaid hold therefore keeps
     * blocking inserts until the sweeper marks it expired, which is why the
     * expiration command runs every minute and the reservation service also
     * sweeps stale holds before reporting a conflict.
     */
    public function up(): void
    {
        // The exclusion constraint is PostgreSQL-specific. SQLite is useful
        // for a lightweight local/container smoke run, but cannot express
        // this constraint; the reservation action still performs its
        // application-level conflict check there. Production should use
        // PostgreSQL so the database guard is enabled.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // PostgreSQL ships the GiST operator class for scalar bigint values
        // in btree_gist. The exclusion constraint combines court_id equality
        // with timestamp-range overlap, so the extension must exist first.
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        DB::statement(<<<'SQL'
            ALTER TABLE reservations
                ADD CONSTRAINT reservations_no_overlapping_holds
                EXCLUDE USING gist (
                    court_id WITH =,
                    tstzrange(starts_at, ends_at, '[)') WITH &&
                )
                WHERE (status IN (
                    'pending',
                    'confirmed',
                    'completed'
                ))
        SQL);

        DB::statement('COMMENT ON CONSTRAINT reservations_no_overlapping_holds ON reservations IS '
            ."'Blocks two reservations from occupying the same court at the same time.'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE reservations DROP CONSTRAINT IF EXISTS reservations_no_overlapping_holds');
    }

    /**
     * Statuses covered by the guard, kept in sync with the enum.
     *
     * @return list<string>
     */
    private function blockingStatuses(): array
    {
        return ReservationStatus::blockingStatuses();
    }
};
