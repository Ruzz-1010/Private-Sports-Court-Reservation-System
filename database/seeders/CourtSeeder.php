<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;

/**
 * Sample courts for the San Jose facility.
 *
 * Rates are development placeholders until the owner confirms pricing.
 */
class CourtSeeder extends Seeder
{
    public function run(): void
    {
        $courts = [
            ['name' => 'Basketball Court A', 'location' => 'Main Facility, Barangay San Jose', 'hourly_rate' => 500.00, 'description' => 'Full-size indoor basketball court with lighting.'],
            ['name' => 'Basketball Court B', 'location' => 'Main Facility, Barangay San Jose', 'hourly_rate' => 450.00, 'description' => 'Half-court basketball, suitable for drills.'],
            ['name' => 'Badminton Court 1', 'location' => 'Main Facility, Barangay San Jose', 'hourly_rate' => 250.00, 'description' => 'Regulation badminton court.'],
            ['name' => 'Badminton Court 2', 'location' => 'Main Facility, Barangay San Jose', 'hourly_rate' => 250.00, 'description' => 'Regulation badminton court.'],
            ['name' => 'Volleyball Court', 'location' => 'Main Facility, Barangay San Jose', 'hourly_rate' => 600.00, 'description' => 'Indoor volleyball court with net.'],
        ];

        foreach ($courts as $court) {
            Court::query()->updateOrCreate(
                ['name' => $court['name']],
                $court + ['is_active' => true],
            );
        }

        $this->command?->info('Courts seeded.');
    }
}
