<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One grant on a folder: who, and what they may do there.
 */
class FolderPolicy extends Model
{
    public const SUBJECTS = ['office', 'user', 'everyone'];

    public const LEVELS = ['view', 'edit', 'manage'];

    protected $fillable = [
        'folder_id',
        'subject_type',
        'subject',
        'level',
        'granted_by_id',
        'granted_by',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    /** Does this grant apply to the given person? */
    public function covers(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return match ($this->subject_type) {
            'everyone' => true,
            'office' => filled($user->office) && $user->office === $this->subject,
            'user' => (string) $user->id === (string) $this->subject,
            default => false,
        };
    }

    /** "Everyone", "ODG" or a person's name, for the sharing list. */
    public function describe(): string
    {
        return match ($this->subject_type) {
            'everyone' => 'Everyone signed in',
            'office' => (string) $this->subject,
            'user' => User::find($this->subject)?->name ?? 'Someone who has left',
            default => 'Unknown',
        };
    }
}
