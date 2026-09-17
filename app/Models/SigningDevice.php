<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A device the signer chose to remember. Opening a signing session on it needs
 * the signing PIN only; the authenticator code is what the device remembers.
 */
class SigningDevice extends Model
{
    /** How long a remembered device stays remembered. */
    public const DAYS = 30;

    protected $fillable = [
        'user_id',
        'token_hash',
        'label',
        'ip_address',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Remember this device and return the plain token for the browser to keep.
     */
    public static function remember(User $user, ?string $label, ?string $ip): string
    {
        $token = Str::random(64);

        static::create([
            'user_id' => $user->id,
            'token_hash' => static::hash($token),
            'label' => $label ? Str::limit($label, 250, '') : null,
            'ip_address' => $ip,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(static::DAYS),
        ]);

        return $token;
    }

    /** The live record for this token, if the device is still remembered. */
    public static function find(User $user, ?string $token): ?self
    {
        if (blank($token)) {
            return null;
        }

        return static::where('user_id', $user->id)
            ->where('token_hash', static::hash($token))
            ->where('expires_at', '>', now())
            ->first();
    }

    /** Forget every device for this signer -- used when credentials change. */
    public static function forgetAll(User $user): void
    {
        static::where('user_id', $user->id)->delete();
    }
}
