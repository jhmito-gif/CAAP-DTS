<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 'user_id', 'body', 'is_token', 'record_id', 'read_at', 'expires_at',
    ];

    protected $casts = [
        'is_token' => 'boolean',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ChatAttachment::class);
    }

    public function expired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Reveal a burn-after-read token: decrypt it, delete the message so it can
     * never be read again, and return the plaintext once.
     */
    public function revealAndBurn(): ?string
    {
        if (! $this->is_token) {
            return null;
        }

        $plain = null;

        try {
            $plain = Crypt::decryptString($this->body);
        } catch (\Throwable $e) {
            $plain = null;
        }

        $this->delete();

        return $plain;
    }
}
