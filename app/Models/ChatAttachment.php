<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatAttachment extends Model
{
    protected $fillable = [
        'message_id',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'is_encrypted',
        'expires_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_encrypted' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /** Files whose retention window has passed. */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Decrypted bytes (or raw bytes for legacy plaintext files). */
    public function contents(): string
    {
        $raw = Storage::disk($this->disk)->get($this->path);

        return $this->is_encrypted ? Crypt::decryptString($raw) : $raw;
    }

    /**
     * Delete the underlying file when the row is removed, so a purge (or a
     * cascade) never leaves orphaned bytes on disk.
     */
    protected static function booted(): void
    {
        static::deleting(function (ChatAttachment $attachment) {
            if ($attachment->path) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
        });
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB'];
        $value = $bytes / 1024;
        $i = 0;

        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $value >= 10 ? 0 : 1) . ' ' . $units[$i];
    }

    public function getIsImageAttribute(): bool
    {
        return Str::startsWith((string) $this->mime_type, 'image/');
    }

    public function getExtensionAttribute(): string
    {
        return strtoupper(pathinfo($this->original_name, PATHINFO_EXTENSION) ?: 'FILE');
    }
}
