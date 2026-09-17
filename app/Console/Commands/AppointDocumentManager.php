<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Appoints (or stands down) an office's document manager: the person who may
 * create and rearrange folders in their office's document library.
 *
 * Admins can do this from the admin panel too; this is for the first one, or
 * for setting several at once from the server.
 */
class AppointDocumentManager extends Command
{
    protected $signature = 'documents:manager
                            {email* : the people to appoint, by email}
                            {--revoke : stand them down instead}
                            {--list : show who looks after each library}';

    protected $description = "Let someone organise their office's document library";

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listManagers();
        }

        $revoking = (bool) $this->option('revoke');
        $changed = 0;

        foreach ($this->argument('email') as $email) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->error("No account with the email {$email}.");

                continue;
            }

            $user->forceFill(['manages_documents' => ! $revoking])->save();
            $changed++;

            $this->info(sprintf(
                '%s %s the %s document library.',
                $user->name,
                $revoking ? 'no longer looks after' : 'now looks after',
                $user->office ?: 'unassigned',
            ));
        }

        if ($changed === 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function listManagers(): int
    {
        $managers = User::query()
            ->where('manages_documents', true)
            ->orderBy('office')
            ->orderBy('name')
            ->get(['name', 'email', 'office']);

        if ($managers->isEmpty()) {
            $this->warn('No one is appointed yet. Admins can still organise every library.');

            return self::SUCCESS;
        }

        $this->table(
            ['Office', 'Name', 'Email'],
            $managers->map(fn (User $user) => [$user->office ?: '—', $user->name, $user->email])->all(),
        );

        return self::SUCCESS;
    }
}
