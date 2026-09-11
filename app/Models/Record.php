<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Record extends Model
{
    protected $fillable = [
        'reference',
        'origin_reference',
        'subject',
        'created_by',
        'origin',
        'owner',
        'is_urgent',
        'is_confidential',
    ];


    protected $casts = [
        'is_urgent' => 'boolean',
        'is_confidential' => 'boolean',
    ];


    /*
    |--------------------------------------------------------------------------
    | Transactions Relationship
    |--------------------------------------------------------------------------
    */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'record_id');
    }


    /*
    |--------------------------------------------------------------------------
    | Tagged Personnel
    |--------------------------------------------------------------------------
    | The people this document is about. Optional -- a record may have none.
    */
    public function taggings(): HasMany
    {
        return $this->hasMany(RecordTagging::class, 'record_id');
    }


    public function taggedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'record_taggings')
            ->withPivot(['office', 'tagged_by'])
            ->withTimestamps();
    }


    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    | Files attached to the record (e.g. the outgoing document itself).
    */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'record_id')->latest();
    }


    /*
    |--------------------------------------------------------------------------
    | Access
    |--------------------------------------------------------------------------
    | An office may view a record when it owns it, is in its routing chain,
    | has someone tagged on it, or was granted explicit access. Mirrors the
    | checks in TransactionTable / PdfController.
    */
    public function isAccessibleBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $office = $user->office;

        if ($this->owner === $office || $this->origin === $office) {
            return true;
        }

        $inRouting = $this->transactions()
            ->where(fn ($query) => $query->where('destination', $office)->orWhere('office', $office))
            ->exists();

        if ($inRouting) {
            return true;
        }

        $isTagged = RecordTagging::where('record_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($isTagged) {
            return true;
        }

        return Access::where('record_id', $this->id)
            ->where('office', $office)
            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | Confidential visibility
    |--------------------------------------------------------------------------
    | A confidential record still flows through the routing chain (offices know
    | it arrived), but the subject, remarks and attachments are visible only to
    | authorised viewers: the owning office, tagged personnel, offices/people
    | an admin granted, and admins themselves. Everyone else who can reach the
    | record sees only that a confidential record exists.
    */
    public function canViewConfidentialDetails(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $office = $user->office;

        if ($this->owner === $office || $this->origin === $office) {
            return true;
        }

        $isTagged = RecordTagging::where('record_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($isTagged) {
            return true;
        }

        return Access::where('record_id', $this->id)
            ->where('office', $office)
            ->exists();
    }

    /**
     * True when the record is confidential and this user is NOT cleared to see
     * its details -- i.e. the subject/files must be masked for them.
     */
    public function isMaskedFor(?User $user): bool
    {
        return $this->is_confidential && ! $this->canViewConfidentialDetails($user);
    }

    /*
    |--------------------------------------------------------------------------
    | Confidential access token
    |--------------------------------------------------------------------------
    | A confidential record carries a hashed token. Even cleared viewers must
    | supply it to open the RAS or its files. Storing it hashed means it can be
    | reset (no data is ever lost) but never read back.
    */
    public function requiresToken(): bool
    {
        return $this->is_confidential && filled($this->confidential_token);
    }

    public function setConfidentialToken(?string $plain): void
    {
        $this->confidential_token = filled($plain) ? \Illuminate\Support\Facades\Hash::make($plain) : null;
    }

    public function checkConfidentialToken(?string $plain): bool
    {
        if (! $this->requiresToken()) {
            return true;
        }

        return filled($plain) && \Illuminate\Support\Facades\Hash::check($plain, $this->confidential_token);
    }


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */
    public function scopeSearch($query, $value)
    {
        if (blank($value)) {
            return $query;
        }

        return $query->where(function ($q) use ($value) {

            $q->where('reference', 'like', "%{$value}%")
                ->orWhere('origin_reference', 'like', "%{$value}%")
                ->orWhere('subject', 'like', "%{$value}%");

        });
    }


    /*
    |--------------------------------------------------------------------------
    | Urgent
    |--------------------------------------------------------------------------
    */
    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }


    /*
    |--------------------------------------------------------------------------
    | Records Associated With An Office
    |--------------------------------------------------------------------------
    */
    public function scopeForOffice($query, $office)
    {
        return $query->where(function ($q) use ($office) {

            $q->where('owner', $office)
                ->orWhere('origin', $office)
                ->orWhereHas('transactions', function ($transactionQuery) use ($office) {

                    $transactionQuery->where(function ($transaction) use ($office) {

                        $transaction->where('office', $office)
                            ->orWhere('destination', $office);

                    });

                });

        });
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Related Transactions
    |--------------------------------------------------------------------------
    */
    protected static function booted()
    {
        static::deleting(function ($record) {
            $record->transactions()->delete();

            // Delete via the model (not a bulk query) so each file is removed.
            $record->attachments->each->delete();
        });
    }
}
