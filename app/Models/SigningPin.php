<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

/**
 * A user's personal signing PIN, stored hashed. Confirms each signature in
 * place of the account password.
 */
class SigningPin extends Model
{
    public const MIN_LENGTH = 6;

    public const MAX_LENGTH = 8;

    protected $fillable = [
        'user_id',
        'pin',
    ];

    protected $hidden = [
        'pin',
    ];

    protected $casts = [
        'pin' => 'hashed',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matches(string $pin): bool
    {
        return $pin !== '' && Hash::check($pin, $this->pin);
    }

    /**
     * Repeated (111111) or sequential (123456, 876543) PINs are refused.
     */
    public static function isGuessable(string $pin): bool
    {
        $digits = array_map('intval', str_split($pin));

        if (count(array_unique($digits)) === 1) {
            return true;
        }

        $steps = [];

        for ($i = 1; $i < count($digits); $i++) {
            $steps[] = $digits[$i] - $digits[$i - 1];
        }

        $steps = array_unique($steps);

        return count($steps) === 1 && in_array(reset($steps), [1, -1], true);
    }
}
