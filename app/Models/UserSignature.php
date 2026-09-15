<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's saved signature image (PNG), encrypted at rest and only ever
 * applied to a document by the server after the user re-verifies.
 */
class UserSignature extends Model
{
    protected $fillable = [
        'user_id',
        'image',
    ];

    protected $hidden = [
        'image',
    ];

    protected $casts = [
        'image' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The signature as raw PNG bytes.
     */
    public function png(): string
    {
        return base64_decode($this->image);
    }

    /**
     * Inline preview for the owner's own screens.
     */
    public function dataUri(): string
    {
        return 'data:image/png;base64,' . $this->image;
    }
}
