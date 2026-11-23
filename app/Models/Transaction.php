<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'record_id', 
        'internal_reference',
        'origin_reference',
        'remarks', 
        'status', 
        'destination', 
        'recieved_by', 
        'date_recieved', 
        'forwarded_by', 
        'office', 
        'service',
        'received_reference',
    ];

    public function scopeSearch($query, $value)
    {
        if (empty($value)) return $query;

        // Reuses the Record search scope as a one-off subquery (not a per-row EXISTS).
        return $query->whereIn('record_id', Record::query()->select('id')->search($value));
    }

    /**
     * Only the office a movement was sent to can say it arrived. Letting the
     * sender (or any office in the chain) stamp it received would put a name
     * on the trail from the wrong office and hide that the document never got
     * there. Admins may still receive on an office's behalf.
     */
    public function isReceivableBy(?User $user): bool
    {
        if (! $user || $this->date_recieved !== null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return filled($user->office) && $this->destination === $user->office;
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class, 'record_id');
    }
}
