<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the first administrator account.
 *
 * AGENTS.md requires the admin to come from a seeder rather than a publicly
 * selectable registration role. The password is read from the environment and
 * must be supplied on the first run; it is never committed.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (! is_string($password) || $password === '') {
            $this->command?->warn('ADMIN_PASSWORD is not set; skipping admin seeder.');

            return;
        }

        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@court.local')],
            [
                'name' => env('ADMIN_NAME', 'Court Administrator'),
                'password' => Hash::make($password),
                'role' => Role::Admin,
                'email_verified_at' => now(),
            ],
        );

        $this->command?->info('Admin account ready.');
    }
}
