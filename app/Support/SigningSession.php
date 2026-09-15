<?php

namespace App\Support;

use App\Models\User;

/**
 * Remembers, for the current login session, that the signer entered their
 * authenticator code while signing, so later signatures need only the signing
 * PIN. The confirmation ends on logout, after MAX_HOURS, when two-factor
 * authentication is reset, or when signing is locked out.
 */
class SigningSession
{
    public const MAX_HOURS = 8;

    private const KEY = 'esign.two_factor';

    public function isConfirmed(User $user): bool
    {
        $confirmation = session(self::KEY);

        return is_array($confirmation)
            && hash_equals($this->fingerprint($user), (string) ($confirmation['fingerprint'] ?? ''))
            && (int) ($confirmation['confirmed_at'] ?? 0) > now()->subHours(self::MAX_HOURS)->getTimestamp();
    }

    public function confirm(User $user): void
    {
        session()->put(self::KEY, [
            'fingerprint' => $this->fingerprint($user),
            'confirmed_at' => now()->getTimestamp(),
        ]);
    }

    public function forget(): void
    {
        session()->forget(self::KEY);
    }

    /**
     * Tied to the user and their current authenticator secret.
     */
    private function fingerprint(User $user): string
    {
        return hash_hmac('sha256', $user->id . '|' . $user->two_factor_secret, (string) config('app.key'));
    }
}
