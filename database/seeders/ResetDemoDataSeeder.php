<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Reset local presentation data without deleting administrator or test users.
 *
 * Run explicitly with:
 * php artisan db:seed --class=ResetDemoDataSeeder
 */
class ResetDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            DB::statement(<<<'SQL'
                TRUNCATE TABLE
                    audit_logs,
                    receipts,
                    payment_events,
                    payments,
                    reservations,
                    court_blocks,
                    courts,
                    jobs,
                    job_batches,
                    failed_jobs,
                    sessions,
                    cache,
                    cache_locks,
                    password_reset_tokens
                RESTART IDENTITY CASCADE
            SQL);
        });

        $this->call(CourtSeeder::class);
        $this->command?->info('Demo data reset. Existing admin and test-user accounts were preserved.');
    }
}
