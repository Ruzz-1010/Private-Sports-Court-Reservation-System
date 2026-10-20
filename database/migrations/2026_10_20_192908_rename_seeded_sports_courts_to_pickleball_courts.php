<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename only the original sample data so existing reservations continue to
     * reference the same court records after the facility scope changes.
     */
    public function up(): void
    {
        $renames = [
            'Basketball Court A' => 'Pickleball Court 1',
            'Basketball Court B' => 'Pickleball Court 2',
            'Badminton Court 1' => 'Pickleball Court 3',
            'Badminton Court 2' => 'Pickleball Court 4',
            'Volleyball Court' => 'Pickleball Court 5',
        ];

        foreach ($renames as $from => $to) {
            DB::table('courts')
                ->where('name', $from)
                ->whereNull('deleted_at')
                ->update([
                    'name' => $to,
                    'image_path' => null,
                    'description' => 'Indoor pickleball court with a regulation net and daytime lighting.',
                    'hourly_rate' => 250.00,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * The requested facility rebrand is intentionally forward-only. Restoring
     * sports-specific labels would make historical reservation records unclear.
     */
    public function down(): void
    {
        // Intentionally irreversible.
    }
};
