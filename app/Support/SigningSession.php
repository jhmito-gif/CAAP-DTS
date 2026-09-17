<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Two things, both tied to the browser session:
 *
 * 1. The authenticator code entered while signing one document at a time --
 *    asked once per login, not per signature.
 *
 * 2. A signing session: opened deliberately with the signing PIN (and the
 *    code, unless the device is remembered), after which each document is
 *    signed with a single click. It closes on logout, when the signer ends it,
 *    after MAX_DOCUMENTS signatures, after IDLE_MINUTES untouched, or
 *    MAX_HOURS after it was opened.
 */
class SigningSession
{
    public const MAX_HOURS = 8;

    public const IDLE_MINUTES = 30;

    public const MAX_DOCUMENTS = 100;

    private const KEY = 'esign.two_factor';

    private const SESSION_KEY = 'esign.session';

    /*
    |--------------------------------------------------------------------------
    | Authenticator code, once per login
    |--------------------------------------------------------------------------
    */

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
        $this->close();
    }

    /*
    |--------------------------------------------------------------------------
    | Signing session
    |--------------------------------------------------------------------------
    */

    public function open(User $user): void
    {
        session()->put(self::SESSION_KEY, [
            'fingerprint' => $this->fingerprint($user),
            'opened_at' => now()->getTimestamp(),
            'last_at' => now()->getTimestamp(),
            'signed' => 0,
        ]);
    }

    public function close(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function isOpen(User $user): bool
    {
        return $this->state($user) !== null;
    }

    /**
     * The live session for this user, or null when there is none.
     *
     * @return array{opened_at: int, last_at: int, signed: int}|null
     */
    public function state(User $user): ?array
    {
        $state = session(self::SESSION_KEY);

        if (! is_array($state) || ! hash_equals($this->fingerprint($user), (string) ($state['fingerprint'] ?? ''))) {
            return null;
        }

        $openedAt = (int) ($state['opened_at'] ?? 0);
        $lastAt = (int) ($state['last_at'] ?? 0);
        $signed = (int) ($state['signed'] ?? 0);

        $expired = $openedAt <= now()->subHours(self::MAX_HOURS)->getTimestamp()
            || $lastAt <= now()->subMinutes(self::IDLE_MINUTES)->getTimestamp()
            || $signed >= self::MAX_DOCUMENTS;

        if ($expired) {
            $this->close();

            return null;
        }

        return ['opened_at' => $openedAt, 'last_at' => $lastAt, 'signed' => $signed];
    }

    /**
     * Count a signature against the session, closing it once the cap is reached.
     */
    public function recordSignature(User $user): void
    {
        $state = $this->state($user);

        if ($state === null) {
            return;
        }

        $signed = $state['signed'] + 1;

        if ($signed >= self::MAX_DOCUMENTS) {
            $this->close();

            return;
        }

        session()->put(self::SESSION_KEY, [
            'fingerprint' => $this->fingerprint($user),
            'opened_at' => $state['opened_at'],
            'last_at' => now()->getTimestamp(),
            'signed' => $signed,
        ]);
    }

    /** Documents that may still be signed under this session. */
    public function remaining(User $user): int
    {
        $state = $this->state($user);

        return $state === null ? 0 : max(0, self::MAX_DOCUMENTS - $state['signed']);
    }

    /** When the session lapses if nothing else happens. */
    public function expiresAt(User $user): ?Carbon
    {
        $state = $this->state($user);

        if ($state === null) {
            return null;
        }

        $idle = Carbon::createFromTimestamp($state['last_at'])->addMinutes(self::IDLE_MINUTES);
        $hard = Carbon::createFromTimestamp($state['opened_at'])->addHours(self::MAX_HOURS);

        return $idle->lt($hard) ? $idle : $hard;
    }

    /**
     * Tied to the user and their current authenticator secret.
     */
    private function fingerprint(User $user): string
    {
        return hash_hmac('sha256', $user->id . '|' . $user->two_factor_secret, (string) config('app.key'));
    }
}
