<?php

namespace App\Services\Courts;

use App\Models\Court;
use Illuminate\Support\Facades\DB;

/**
 * Deletes or archives a court depending on whether history depends on it.
 *
 * A court referenced by any reservation or schedule block is soft-deleted so
 * past bookings, payments, and receipts keep pointing at a real record. An
 * unused court is removed permanently.
 */
class DeleteCourt
{
    /**
     * Determine whether a court is referenced by anything that must be preserved.
     */
    public function hasHistory(Court $court): bool
    {
        return $court->reservations()->exists()
            || $court->blocks()->exists();
    }

    /**
     * @return array{archived: bool, court: Court}
     */
    public function handle(Court $court): array
    {
        if (! $this->hasHistory($court)) {
            // Safe to remove permanently: nothing references this record.
            DB::transaction(function () use ($court): void {
                $court->forceDelete();
            });

            return ['archived' => false, 'court' => $court];
        }

        // History exists, so archive instead of destroying the record.
        DB::transaction(function () use ($court): void {
            $court->update(['is_active' => false]);
            $court->delete();
        });

        return ['archived' => true, 'court' => $court];
    }
}
