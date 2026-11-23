<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A sample login for trying the system as an ordinary office user.
 *
 *     php artisan db:seed --class=SampleAccountSeeder
 *
 * Safe to run again: it resets the account rather than adding another.
 * Refuses to run in production -- a known password must never exist there.
 */
class SampleAccountSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /** email => [name, office, role] */
    public const ACCOUNTS = [
        'sample.odg@example.com' => ['Sample ODG User', 'ODG', User::ROLE_USER],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Sample accounts are not seeded in production.');

            return;
        }

        foreach (self::ACCOUNTS as $email => [$name, $office, $role]) {
            $user = User::firstOrNew(['email' => $email]);

            $user->forceFill([
                'name' => $name,
                'office' => $office,
                'role' => $role,
                'manages_documents' => false,
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ])->save();

            $this->command?->info("{$email} / " . self::PASSWORD . " ({$office}, {$role})");
        }
    }
}
