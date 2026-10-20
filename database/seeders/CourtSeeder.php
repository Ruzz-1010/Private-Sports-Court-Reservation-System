<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;

/**
 * Sample pickleball courts for the San Jose facility.
 *
 * Rates are development placeholders until the owner confirms pricing.
 */
class CourtSeeder extends Seeder
{
    public function run(): void
    {
        $courts = [
            ['name' => 'Pickleball Court 1', 'image_path' => null, 'location' => 'Main Facility, Barangay San Jose', 'hourly_rate' => 250.00, 'description' => 'Indoor pickleball court with a regulation net and daytime lighting.'],
            ['name' => 'Pickleball Court 2', 'image_path' => null, 'location' => 'Main Facility, Barangay San Jose', 'hourly_rate' => 250.00, 'description' => 'Indoor pickleball court with a regulation net and daytime lighting.'],
            ['name' => 'Pickleball Court 3', 'image_path' => null, 'location' => 'Main Facility, Barangay San Jose', 'hourly_rate' => 250.00, 'description' => 'Indoor pickleball court with a regulation net and daytime lighting.'],
            ['name' => 'Pickleball Court 4', 'image_path' => null, 'location' => 'Main Facility, Barangay San Jose', 'hourly_rate' => 250.00, 'description' => 'Indoor pickleball court with a regulation net and daytime lighting.'],
            ['name' => 'Pickleball Court 5', 'image_path' => null, 'location' => 'Main Facility, Barangay San Jose', 'hourly_rate' => 250.00, 'description' => 'Indoor pickleball court with a regulation net and daytime lighting.'],
        ];

        foreach ($courts as $court) {
            Court::query()->updateOrCreate(
                ['name' => $court['name']],
                $court + ['is_active' => true],
            );
        }

        $this->command?->info('Pickleball courts seeded.');
    }
}
